<?php

namespace Tempcord\Contracts;

use Ragnarok\Fenrir\Interaction\CommandInteraction;

interface Autocomplete
{
    public function handle(CommandInteraction $interaction, mixed $value): mixed;

}