<?php

namespace App\Commands\Permissions;

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
        #[Option(
            description: 'The role to edit',
            required: true
        )]
        Role         $role,
        #[Option(
            description: 'The channel permissions to edit. If omitted, the guild permissions will be returned',
            required: false
        )]
        Channel|null $channel = null
    ): void
    {

    }

    #[Subcommand(name: 'get', description: 'Get permissions for a role')]
    public function get(
        #[Option(
            description: 'The role to get',
            required: true
        )]
        Role         $role,
        #[Option(
            description: 'The channel permissions to edit. If omitted, the guild permissions will be returned',
            required: false
        )]
        Channel|null $channel = null
    ): void
    {

    }
}