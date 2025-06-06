<?php

namespace Tempcord\Logging;

use Monolog\Level;
use Tempcord\Logging\Handlers\ConsoleLogHandler;
use Tempest\Console\Console;
use Tempest\Log\LogChannel;
use function Tempest\get;

final readonly class ConsoleLogChannel implements LogChannel
{
    public function __construct(
        private array $except = [],
    )
    {
    }

    public function getHandlers(Level $level): array
    {
        return [
            new ConsoleLogHandler(
                console: get(Console::class),
                except: $this->except,
                level: $level
            ),
        ];
    }

    public function getProcessors(): array
    {
        return [];
    }
}