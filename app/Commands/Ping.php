<?php

declare(strict_types=1);

namespace App\Commands;

use Discord\Builders\Components\Button;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Exception;
use React\Promise\PromiseInterface;
use Tempcord\Attributes\SlashCommands\Command;
use Tempcord\Attributes\SlashCommands\Option;
use Tempcord\AutoCompletes\ArrayAutocomplete;
use Tempcord\Helpers\MessageBuilder;

#[Command(
    description: 'Ping? Pong!',
)]
final class Ping
{
    //@todo AdminOnlyCommand (command middleware based, we need to add middlewares to Command
    //  Something like: AdminOnlyCommand(admin_id: - optional, take from config if not set)
    //  Middlewares will allow us to check other stuff before executing the command

    //@todo add support from sub commands
    //  The easies way - mark command as SubCommand and every option as subcommand options,
    //  mapped to own commands (by class-name).
    //  Something like: Option(type: SubCommand(command: SubCommandClass::class))
    //  we can not construct that in __construct method, this method is for constructing the command for running.
    //  SubCommand registration is more meta configuration that runtime config

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
