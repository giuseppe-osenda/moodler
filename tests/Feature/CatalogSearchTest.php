<?php

namespace Tests\Feature;

use App\Data\CatalogEntry;
use App\Services\CatalogSearch;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['services.openai.key' => 'sk-test']);
    }

    /**
     * @param  list<array<string, mixed>>  $results
     */
    private function fakeCatalog(array $results): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response(['status' => 'completed', 'output' => [
            ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['results' => $results])]]],
        ]])]);
    }

    public function test_it_returns_entries_in_the_interface_language_and_drops_duplicates_and_blanks(): void
    {
        $this->fakeCatalog([
            ['value' => 'Shoegaze', 'label' => 'Shoegaze', 'description' => 'Chitarre sognanti e voci sommerse.'],
            ['value' => 'shoegaze', 'label' => 'Shoegaze', 'description' => 'Doppione.'],
            ['value' => '', 'label' => 'Vuoto', 'description' => ''],
            ['value' => 'Dream Pop', 'label' => 'Dream pop', 'description' => 'Pop etereo e nebbioso.'],
        ]);

        $entries = app(CatalogSearch::class)->search('genres', ' shoegaze ', 'it');

        $this->assertEquals([
            new CatalogEntry('Shoegaze', 'Shoegaze', 'Chitarre sognanti e voci sommerse.'),
            new CatalogEntry('Dream Pop', 'Dream pop', 'Pop etereo e nebbioso.'),
        ], $entries);

        Http::assertSent(fn (Request $request): bool => $request['text']['format']['name'] === 'catalog_search'
            && str_contains($request['input'], 'The listener typed: "shoegaze"')
            && str_contains($request['input'], 'music genres')
            && str_contains($request['input'], 'one sentence in Italiano'));
    }

    public function test_repeated_searches_are_answered_from_the_cache(): void
    {
        $this->fakeCatalog([['value' => 'Wistful', 'label' => 'Malinconico', 'description' => 'Un dolce rimpianto.']]);

        $catalog = app(CatalogSearch::class);
        $catalog->search('moods', 'Malinconico', 'it');
        $entries = $catalog->search('moods', 'malinconico ', 'it');

        $this->assertSame('Wistful', $entries[0]->value);
        Http::assertSentCount(1);
    }

    public function test_it_rejects_fields_that_cannot_be_searched(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CatalogSearch::class)->search('relationship', 'friends', 'en');
    }
}
