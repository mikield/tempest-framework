<?php

namespace Tempcord\ConsoleCommands;

use Tempcord\Tempcord;
use Tempest\Console\ConsoleCommand;

final readonly class BotCommand
{
    public function __construct(
        private Tempcord $tempcord,
    )
    {
    }

    #[ConsoleCommand]
    public function boot(): void
    {
        //@TODO: add verbosity lvl and pass it somehow to logger
        $this->tempcord->boot();
    }
}