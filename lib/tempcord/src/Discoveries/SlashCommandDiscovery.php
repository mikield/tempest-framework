<?php

namespace Tempcord\Discoveries;

use Tempcord\Attributes\SlashCommands\Command;
use Tempcord\Registries\CommandRegistry;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class SlashCommandDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly CommandRegistry $commandRegistry,
    )
    {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getAttributes(Command::class) as $attribute) {
            $attribute->setReflector($class);
            $this->discoveryItems->add($location, $attribute);
        }
    }

    /**
     * @mago-expect best-practices/no-empty-loop
     */
    public function apply(): void
    {
        foreach ($this->discoveryItems as $command) {
            $this->commandRegistry->add($command);
        }
    }
}
