<?php

namespace Kevariable\SlackLogging;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class SlackLogging
{
    /**
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function handle(\Throwable $exception): bool
    {
        if ($this->isSkipEnvironment()) {
            return false;
        }

        $data = $this->getExceptionData($exception);

        if ($this->isSkipException($data['class'])) {
            return false;
        }

        if ($this->isSleepingException($data)) {
            return false;
        }

        $this->logError($data);

        return true;
    }

    public function isSkipEnvironment(): bool
    {
        if (count(config('slack-logging.environments')) == 0) {
            return true;
        }

        if (in_array(App::environment(), config('slack-logging.environments'))) {
            return false;
        }

        return true;
    }

    public function getExceptionData(\Throwable $exception): array
    {
        $data = [];

        $data['environment'] = App::environment();
        $data['host'] = Request::server('SERVER_NAME');
        $data['method'] = Request::method();
        $data['fullUrl'] = Request::fullUrl();
        $data['exception'] = $exception->getMessage();
        $data['error'] = $exception->getTraceAsString();
        $data['line'] = $exception->getLine();
        $data['file'] = $exception->getFile();
        $data['class'] = get_class($exception);
        $data['storage'] = [
            'SERVER' => [
                'USER' => Request::server('USER'),
                'HTTP_USER_AGENT' => Request::server('HTTP_USER_AGENT'),
                'SERVER_PROTOCOL' => Request::server('SERVER_PROTOCOL'),
                'SERVER_SOFTWARE' => Request::server('SERVER_SOFTWARE'),
                'PHP_VERSION' => PHP_VERSION,
            ],
            'OLD' => Request::hasSession() ? Request::old() : [],
            'COOKIE' => Request::cookie(),
            'SESSION' => Request::hasSession() ? Session::all() : [],
            'HEADERS' => Request::header(),
            'PARAMETERS' => $this->filterParameterValues(Request::all()),
        ];

        $data['storage'] = array_filter($data['storage']);

        $count = config('slack-logging.lines_count');

        if ($count > 50) {
            $count = 12;
        }

        $lines = file($data['file']);
        $data['executor'] = [];

        if (count($lines) < $count) {
            $count = count($lines) - $data['line'];
        }

        for ($i = -1 * abs($count); $i <= abs($count); $i++) {
            $data['executor'][] = $this->getLineInfo($lines, $data['line'], $i);
        }
        $data['executor'] = array_filter($data['executor']);

        // to make symfony exception more readable
        if ($data['class'] == 'Symfony\Component\Debug\Exception\FatalErrorException') {
            preg_match("~^(.+)' in ~", $data['exception'], $matches);
            if (isset($matches[1])) {
                $data['exception'] = $matches[1];
            }
        }

        return $data;
    }

    public function filterParameterValues(array $parameters): array
    {
        return collect($parameters)->map(function ($value) {
            if ($this->shouldParameterValueBeFiltered($value)) {
                return '...';
            }

            return $value;
        })->toArray();
    }

    /**
     * Determines whether the given parameter value should be filtered.
     */
    public function shouldParameterValueBeFiltered(mixed $value): bool
    {
        return $value instanceof UploadedFile;
    }

    /**
     * Gets information from the line.
     *
     *
     * @return array|void
     */
    private function getLineInfo($lines, $line, $i)
    {
        $currentLine = $line + $i;

        $index = $currentLine - 1;

        if (! array_key_exists($index, $lines)) {
            return;
        }

        return [
            'line_number' => $currentLine,
            'line' => $lines[$index],
        ];
    }

    public function isSkipException($exceptionClass): bool
    {
        return in_array($exceptionClass, config('slack-logging.except'));
    }

    private function logError($exception): void
    {
        $date = date(format: 'Y-m-d H:i:s');

        $slack = (new SlackMessage)
            ->headerBlock(
                text: str(config(key: 'app.name'))
                    ->wrap(before: '[', after: ']')
                    ->append(' New exception thrown')
            )
            ->dividerBlock()
            ->sectionBlock(function (SectionBlock $block) use ($exception, $date) {
                $block->field("*{$exception['exception']}*")->markdown();
                $block->field("*Full URL:*\n{$exception['fullUrl']}")->markdown();
                $block->field("*Class Exception:*\n{$exception['class']}")->markdown();
                $block->field("*File:*\n{$exception['file']}")->markdown();
                $block->field("*Line:*\n{$exception['line']}")->markdown();
                $block->field("*Date:*\n{$date}")->markdown();
            });

        $url = config('slack-logging.webhook_url');

        if (blank($url)) {
            return;
        }

        try {
            Http::post(url: $url, data: $slack->toArray());
        } catch (RequestException $e) {
            $e->getResponse();

            return;
        } catch (\Exception $e) {
            return;
        }
    }

    public function isSleepingException(array $data): bool
    {
        if (config('slack-logging.sleep', 0) == 0) {
            return false;
        }

        return Cache::has($this->createExceptionString($data));
    }

    private function createExceptionString(array $data): string
    {
        return 'slack-logging.'.Str::slug($data['host'].'_'.$data['method'].'_'.$data['exception'].'_'.$data['line'].'_'.$data['file'].'_'.$data['class']);
    }

    public function getUser(): ?array
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = auth()->user();

        if ($user instanceof Model) {
            return $user->toArray();
        }

        return null;
    }
}
