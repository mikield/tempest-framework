<?php

namespace Tempcord\Logging\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LogLevel;
use Tempest\Console\Console;
use function Tempest\Support\Arr\map_iterable;
use function Tempest\Support\str;

final class ConsoleLogHandler extends AbstractProcessingHandler
{
    public function __construct(
        private readonly Console $console,
        private readonly array   $except = [],
                                 $level = Level::Debug,
                                 $bubble = true
    )
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (str($record->message)->lower()->contains(map_iterable($this->except, fn(string $message) => str($message)->lower()))) {
            return;
        }

        $type = match ($record->level) {
            Level::Alert => LogLevel::ALERT,
            Level::Critical => LogLevel::CRITICAL,
            Level::Debug => LogLevel::DEBUG,
            Level::Emergency => LogLevel::EMERGENCY,
            Level::Error => LogLevel::ERROR,
            Level::Warning => LogLevel::WARNING,
            default => LogLevel::INFO,
        };

        $message = ucfirst($record->message);

        $context = array_merge($record->context, $record->extra);

        $component = match ($record->level) {
            Level::Alert, Level::Critical, Level::Error, Level::Emergency => 'error',
            Level::Warning => 'warning',
            default => 'info',
        };

        if (method_exists($this->console, 'log')) {
            $this->console->log($type, $message, $context);
        } else {
            $this->console->{$component}($message);
        }
    }
}