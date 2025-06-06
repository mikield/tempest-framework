<?php

namespace Tempcord;

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Gateway\Events\Ready;
use Tempcord\Registries\CommandsRegistry;
use Tempest\Console\Console;
use function Tempest\get;

final class Tempcord
{
    private CommandsRegistry $commandsRegistry;

    public bool $booted = false;

    public function __construct(
        public readonly Discord  $discord,
        private readonly Console $console,
    )
    {
        //@todo: Maybe move to  Interface
        $this->commandsRegistry = get(CommandsRegistry::class);

        $this->discord->gateway->events->on(Events::READY, function (Ready $ready) {
            $this->discord->registerExtension($this->commandsRegistry->extension);
            $this->booted = true;
        });
    }

    public function registerCommands(): void
    {
        $this->commandsRegistry->register(
            console: $this->console,
            discord: $this->discord,
        );
    }

    public function boot(): void
    {
        $this->commandsRegistry->listen(
            console: $this->console
        );

        $this->discord->gateway->open();
    }
}