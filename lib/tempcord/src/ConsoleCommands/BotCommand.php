<?php

namespace Tempcord\ConsoleCommands;

use Tempcord\Tempcord;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;

final readonly class BotCommand
{
    public function __construct(
        private Tempcord $tempcord,
        private Console  $console
    )
    {
    }

    #[ConsoleCommand]
    public function boot(): void
    {
        $this->console->header('Booting up...');
        $this->tempcord->boot();
    }
}