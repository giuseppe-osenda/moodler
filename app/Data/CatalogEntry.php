<?php

namespace App\Data;

/**
 * A mood or genre found by searching beyond the built-in options.
 */
final readonly class CatalogEntry
{
    /**
     * @param  string  $value  English name sent to the AI when composing
     * @param  string  $label  Name shown in the interface language
     * @param  string  $description  One-sentence description in the interface language
     */
    public function __construct(
        public string $value,
        public string $label,
        public string $description,
    ) {}

    /**
     * @return array{value: string, label: string, description: string}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'label' => $this->label, 'description' => $this->description];
    }
}
