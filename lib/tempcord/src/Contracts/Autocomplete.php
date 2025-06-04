<?php

namespace Tempcord\Contracts;

use Discord\Parts\Interactions\Interaction;

interface Autocomplete
{
    public function handle(Interaction $interaction, mixed $value): mixed;

}