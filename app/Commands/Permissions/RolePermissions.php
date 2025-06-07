<?php

namespace App\Commands\Permissions;

use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Enums\MessageFlag;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Ragnarok\Fenrir\Parts\Channel;
use Ragnarok\Fenrir\Parts\Role;
use App\DTO\{SubcommandGroups, Commands};
use Tempcord\Attributes\{Command, Subcommand, Option, SubcommandGroup};

#[Command(name: Commands::PERMISSIONS, description: 'Get or edit permissions for a user or a role')]
#[SubcommandGroup(name: SubcommandGroups::ROLE, description: 'Get or edit permissions for a role')]
final class RolePermissions
{
    #[Subcommand(name: 'edit', description: 'Edit permissions for a role')]
    public function edit(
        CommandInteraction $interaction,
        #[Option(
            description: 'The role to edit',
        )]
        Role               $role,
        #[Option(
            description: 'The channel permissions to edit. If omitted, the guild permissions will be returned',
        )]
        Channel|null       $channel = null
    ): void
    {
        $interaction->createInteractionResponse(
            InteractionCallbackBuilder::new()
                ->setContent('Role: ' . $role->name)
                ->setType(InteractionCallbackType::CHANNEL_MESSAGE_WITH_SOURCE)
                ->setFlags(Bitwise::from(MessageFlag::EPHEMERAL)->getBitSet())
        );
    }

    #[Subcommand(name: 'get', description: 'Get permissions for a role')]
    public function get(
        CommandInteraction $interaction,
        #[Option(
            description: 'The role to get',
        )]
        Role               $role,
        #[Option(
            description: 'The channel permissions to edit. If omitted, the guild permissions will be returned',
        )]
        Channel|null       $channel = null
    ): void
    {
        $interaction->createInteractionResponse(
            InteractionCallbackBuilder::new()
                ->setContent('Role: ' . $role->name)
                ->setType(InteractionCallbackType::CHANNEL_MESSAGE_WITH_SOURCE)
                ->setFlags(Bitwise::from(MessageFlag::EPHEMERAL)->getBitSet())
        );
    }
}