<?php

namespace Tempcord;

use Discord\Discord;
use Tempcord\Registries\CommandRegistry;
use Tempest\Console\Console;

final class Tempcord
{
    public bool $booted = false;

    public function __construct(
        private readonly Discord $discord,
        public CommandRegistry   $commandRegistry,
        private readonly Console $console,
    )
    {
    }

    public function boot(): void
    {
        $this->discord->on('init', function (Discord $discord) {
            $this->booted = true;
            $this->commandRegistry->register($this->console, $discord);
            $this->console->success('Bot started and ready');
        });

        $this->commandRegistry->listen($this->console, $this->discord);

        $this->discord->run();
    }
}