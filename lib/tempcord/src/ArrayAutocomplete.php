<?php

namespace Tempcord;

use Discord\Parts\Interactions\Interaction;
use Tempcord\Contracts\Autocomplete;
use function Tempest\Support\str;

final readonly class ArrayAutocomplete implements Autocomplete
{

    public function __construct(
        private array $items,
        private bool  $isList = false
    )
    {
    }

    public function handle(Interaction $interaction, mixed $value): array
    {
        $result = array_filter($this->items, fn($item) => str($item)->contains($value));


        return $this->isList ? array_values($result) : $result;
    }
}