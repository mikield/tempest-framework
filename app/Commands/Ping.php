<?php

declare(strict_types=1);

namespace App\Commands;

use Discord\Builders\Components\Button;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\User;
use Exception;
use React\Promise\PromiseInterface;
use Tempcord\ArrayAutocomplete;
use Tempcord\Attributes\SlashCommands\Command;
use Tempcord\Attributes\SlashCommands\Option;
use Tempcord\MessageBuilder;

#[Command(
    description: 'Ping? Pong!',
)]
final class Ping
{

    //@todo add autocomplete functionality (property hooks?)

    //@todo add command permissions

    //@todo AdminOnlyCommand

    //@todo add guild_id option to register command only in 1 guild

    //@todo add computed (property hooks) sub commands

    #[Option(
        description: 'Provide users name',
        required: true,
        autocomplete: new ArrayAutocomplete([
            'Vladyslav',
            'Mikield'
        ], isList: true)
    )]
    public string $name;

    public function __construct(
        private readonly MessageBuilder $messageBuilder,
    )
    {
    }

    /**
     * Handles the ping command and replies with "pong".
     *
     * @param Interaction $interaction The Discord command interaction
     * @return PromiseInterface|Message
     * @throws Exception
     */
    public function __invoke(Interaction $interaction): PromiseInterface|Message
    {
        return $this->messageBuilder
            ->info()
            ->content('Pong, ' . $this->name)
            ->button('Laracord Resources', style: Button::STYLE_SECONDARY, id: 'resources')
            ->reply($interaction, ephemeral: true);
    }
}
