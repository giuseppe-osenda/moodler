<?php

namespace App\Services;

use App\Data\CatalogEntry;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Finds real music genres or moods that match what the player typed, including ones
 * that are not among the built-in options. Answers are cached, since the same
 * searches come up again and again.
 */
class CatalogSearch
{
    public const MAX_RESULTS = 6;

    private const CACHE_DAYS = 7;

    /**
     * What each searchable field holds, as described to the AI.
     */
    private const SUBJECTS = [
        'genres' => 'real music genres or subgenres. A search can match by name, spelling, translation or by the sound it describes',
        'moods' => 'moods or emotions a piece of music can evoke. A search can match by name, translation, synonym or by the feeling it describes',
    ];

    public function __construct(private readonly OpenAiClient $openAi) {}

    /**
     * @param  'genres'|'moods'  $field
     * @return list<CatalogEntry>
     */
    public function search(string $field, string $query, string $locale): array
    {
        $subject = self::SUBJECTS[$field] ?? throw new InvalidArgumentException("Unknown catalog field [{$field}].");
        $query = trim($query);
        $cacheKey = "catalog-search:{$field}:{$locale}:".md5(mb_strtolower($query));

        $entries = Cache::remember($cacheKey, now()->addDays(self::CACHE_DAYS), fn (): array => $this->ask($subject, $query, $locale));

        return array_map(fn (array $entry): CatalogEntry => new CatalogEntry(...$entry), $entries);
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    private function ask(string $subject, string $query, string $locale): array
    {
        $language = config("app.supported_locales.{$locale}", 'English');
        $max = self::MAX_RESULTS;

        $result = $this->openAi->json(
            instructions: 'You are a music expert helping a listener search a catalog. You only return things that really exist, and you return nothing rather than guess.',
            prompt: implode("\n", [
                "The listener typed: \"{$query}\"",
                '',
                "Return up to {$max} {$subject}, best match first.",
                'Return an empty list when the text does not point to any, for example random letters or unrelated words.',
                'For each one:',
                '- value: its usual English name in short Title Case',
                "- label: its name in {$language}; always translate moods, keep the English name only for genres usually left untranslated",
                "- description: one sentence in {$language}, at most 15 words, describing how it sounds or feels",
            ]),
            schemaName: 'catalog_search',
            schema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['results'],
                'properties' => [
                    'results' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['value', 'label', 'description'],
                            'properties' => [
                                'value' => ['type' => 'string'],
                                'label' => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        );

        return collect($result['results'] ?? [])
            ->filter(fn (mixed $entry): bool => is_array($entry) && filled($entry['value'] ?? null) && filled($entry['label'] ?? null))
            ->map(fn (array $entry): array => [
                'value' => mb_substr(trim((string) $entry['value']), 0, 40),
                'label' => mb_substr(trim((string) $entry['label']), 0, 40),
                'description' => trim((string) ($entry['description'] ?? '')),
            ])
            ->unique(fn (array $entry): string => mb_strtolower($entry['value']))
            ->take(self::MAX_RESULTS)
            ->values()
            ->all();
    }
}
