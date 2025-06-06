<?php

namespace App\Commands\Permissions;

use Ragnarok\Fenrir\Parts\Channel;
use Ragnarok\Fenrir\Parts\User;
use App\DTO\{SubcommandGroups, Commands};
use Tempcord\Attributes\{Command, Subcommand, Option, SubcommandGroup};

#[Command(name: Commands::PERMISSIONS, description: 'Get or edit permissions for a user or a role')]
#[SubcommandGroup(name: SubcommandGroups::USER, description: 'Get or edit permissions for a user')]
final class UserPermissions
{
    #[Subcommand(name: 'edit', description: 'Edit permissions for a user')]
    public function edit(
        #[Option(
            description: 'The user to edit',
            required: true
        )]
        User         $user,
        #[Option(
            description: 'The channel permissions to edit. If omitted, the guild permissions will be returned',
            required: false
        )]
        Channel|null $channel = null
    ): void
    {

    }

    #[Subcommand(name: 'get', description: 'Get permissions for a user')]
    public function get(
        #[Option(
            description: 'The user to get',
            required: true
        )]
        User         $user,
        #[Option(
            description: 'The channel permissions to edit. If omitted, the guild permissions will be returned',
            required: false
        )]
        Channel|null $channel = null
    ): void
    {

    }
}