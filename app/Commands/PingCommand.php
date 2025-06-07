<?php

namespace App\Commands;

use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Enums\MessageFlag;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Ragnarok\Fenrir\Parts\User;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

#[Command(description: "Ping? Pong!")]
final class PingCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Whom to ping?')]
        User|null          $user = null,
    ): void
    {
        $interaction->createInteractionResponse(
            InteractionCallbackBuilder::new()
                ->setContent('Ping, ' . $user?->username)
                ->setType(InteractionCallbackType::CHANNEL_MESSAGE_WITH_SOURCE)
                ->setFlags(Bitwise::from(MessageFlag::EPHEMERAL)->getBitSet())
        );
    }

}