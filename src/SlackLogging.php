<?php

namespace Kevariable\SlackLogging;

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

        $this->putExceptionToSleep($data);

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

        return $data;
    }

    protected function filterParameterValues(array $parameters): array
    {
        return collect($parameters)->map(function ($value) {
            if ($value instanceof UploadedFile) {
                return '...';
            }

            return $value;
        })->toArray();
    }

    /**
     * Gets information from the line.
     *
     *
     * @return array|void
     */
    protected function getLineInfo($lines, $line, $i)
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

    protected function logError($exception): void
    {
        $date = date(format: 'Y-m-d H:i:s');
        $parameters = $exception['storage']['PARAMETERS'] ?? null;

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
            })
            ->when(
                $parameters !== null,
                fn (SlackMessage $message) => $message
                    ->sectionBlock(function (SectionBlock $block) use ($parameters) {
                        $encodedParameters = json_encode($parameters, JSON_PRETTY_PRINT);

                        $block
                            ->text("*Payload*: ```{$encodedParameters}```")
                            ->markdown();
                    })
            );

        $url = config('slack-logging.webhook_url');

        if (blank($url)) {
            return;
        }

        try {
            Http::post(url: $url, data: $slack->toArray());
        } catch (\Exception) {
            return;
        }
    }

    public function isSleepingException(array $data): bool
    {
        $sleepSteps = $this->getSleepSteps();

        if ($sleepSteps === []) {
            return false;
        }

        $cacheKey = $this->createExceptionString($data);
        $occurrence = Cache::get($cacheKey);

        if ($occurrence === null) {
            return false;
        }

        // Bump occurrence and extend TTL with the next sleep step
        $nextOccurrence = $occurrence + 1;
        $ttl = $sleepSteps[min($nextOccurrence - 1, count($sleepSteps) - 1)];

        Cache::put($cacheKey, $nextOccurrence, $ttl);

        return true;
    }

    protected function putExceptionToSleep(array $data): void
    {
        $sleepSteps = $this->getSleepSteps();

        if ($sleepSteps === []) {
            return;
        }

        Cache::put($this->createExceptionString($data), 1, $sleepSteps[0]);
    }

    protected function getSleepSteps(): array
    {
        $sleep = config('slack-logging.sleep', 0);

        if (is_int($sleep)) {
            return $sleep == 0 ? [] : [$sleep];
        }

        return array_filter((array) $sleep);
    }

    protected function createExceptionString(array $data): string
    {
        return 'slack-logging.'.Str::slug($data['host'].'_'.$data['method'].'_'.$data['exception'].'_'.$data['line'].'_'.$data['file'].'_'.$data['class']);
    }
}
