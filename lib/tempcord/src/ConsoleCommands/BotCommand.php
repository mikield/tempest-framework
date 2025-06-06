<?php

namespace Tempcord\ConsoleCommands;

use Tempcord\Tempcord;
use Tempest\Console\Console;
use Tempest\Console\ConsoleArgument;
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
    public function boot(
        #[ConsoleArgument(
            description: 'Register bot commands',
            aliases: ['r']
        )]
        bool $register
    ): void
    {
        if ($register) {
            $this->console->header('Registering commands...');
            $this->tempcord->registerCommands();
        }

        $this->console->header('Booting up...');
        $this->tempcord->boot();
    }
}