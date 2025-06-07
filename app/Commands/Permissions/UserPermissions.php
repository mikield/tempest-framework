<?php

namespace App\Commands\Permissions;

use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Enums\MessageFlag;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Ragnarok\Fenrir\Parts\Channel;
use Ragnarok\Fenrir\Parts\User;
use App\DTO\{Commands};
use Tempcord\Attributes\{Command, Subcommand, Option};

#[Command(name: Commands::PERMISSIONS, description: 'Get or edit permissions for a user or a role')]
final class UserPermissions
{
    #[Subcommand(name: 'edit', description: 'Edit permissions for a user')]
    public function edit(
        CommandInteraction $interaction,
        #[Option(
            description: 'The user to edit',
        )]
        User               $user,
        #[Option(
            description: 'The channel permissions to edit. If omitted, the guild permissions will be returned',
        )]
        Channel|null       $channel = null,
    ): void
    {
        $interaction->createInteractionResponse(
            InteractionCallbackBuilder::new()
                ->setContent('User: ' . $user->username)
                ->setType(InteractionCallbackType::CHANNEL_MESSAGE_WITH_SOURCE)
                ->setFlags(Bitwise::from(MessageFlag::EPHEMERAL)->getBitSet())
        );
    }

    #[Subcommand(name: 'get', description: 'Get permissions for a user')]
    public function get(
        CommandInteraction $interaction,
        #[Option(
            description: 'The user to get',
        )]
        User               $user,
        #[Option(
            description: 'The channel permissions to edit. If omitted, the guild permissions will be returned',
        )]
        Channel|null       $channel = null
    ): void
    {
        $interaction->createInteractionResponse(
            InteractionCallbackBuilder::new()
                ->setContent('User: ' . $user->username . ' | Channel: ' . $channel?->name)
                ->setType(InteractionCallbackType::CHANNEL_MESSAGE_WITH_SOURCE)
                ->setFlags(Bitwise::from(MessageFlag::EPHEMERAL)->getBitSet())
        );
    }
}