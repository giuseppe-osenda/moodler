<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Livewire\MoodComposer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * @return array<string, array{0: ?string, 1: string}>
     */
    public static function browserLanguages(): array
    {
        return [
            'no preference' => [null, 'it'],
            'French with region' => ['fr-CH, fr;q=0.9, en;q=0.8', 'fr'],
            'first supported language wins' => ['ja-JP, de;q=0.8, en;q=0.5', 'de'],
            'unsupported languages fall back to Italian' => ['ja-JP, zh;q=0.8', 'it'],
        ];
    }

    #[DataProvider('browserLanguages')]
    public function test_the_interface_follows_the_browser_language(?string $acceptLanguage, string $expectedLocale): void
    {
        $headers = ['Accept-Language' => $acceptLanguage ?? ''];

        $this->get('/', $headers)
            ->assertOk()
            ->assertSee('<html lang="'.$expectedLocale.'"', false)
            ->assertSee(trans('moodler.phrase.title', [], $expectedLocale), false);
    }

    public function test_a_saved_language_choice_beats_the_browser_language(): void
    {
        $this->withCookie(SetLocale::COOKIE, 'es')
            ->get('/', ['Accept-Language' => 'en-US'])
            ->assertSee('<html lang="es"', false)
            ->assertSee(trans('moodler.phrase.title', [], 'es'), false);
    }

    public function test_an_unknown_saved_language_is_ignored(): void
    {
        $this->withCookie(SetLocale::COOKIE, 'xx')
            ->get('/', ['Accept-Language' => 'en-US'])
            ->assertSee('<html lang="en"', false);
    }

    public function test_the_selector_lists_every_supported_language(): void
    {
        $response = $this->get('/', ['Accept-Language' => '']);

        foreach (config('app.supported_locales') as $code => $name) {
            $response->assertSee('<option value="'.$code.'" lang="'.$code.'">'.$name.'</option>', false);
        }
    }

    public function test_picking_a_language_translates_the_interface_and_remembers_the_choice(): void
    {
        Livewire::test(MoodComposer::class)
            ->assertSet('locale', 'it')
            ->set('locale', 'en')
            ->assertSee(trans('moodler.phrase.title', [], 'en'))
            ->assertDispatched('locale-changed', locale: 'en');

        $this->assertSame('en', App::getLocale());
        $this->assertSame('en', Cookie::queued(SetLocale::COOKIE)?->getValue());
    }

    public function test_an_unsupported_language_cannot_be_picked(): void
    {
        Livewire::test(MoodComposer::class)
            ->set('locale', 'xx')
            ->assertSet('locale', 'it')
            ->assertNotDispatched('locale-changed');

        $this->assertNull(Cookie::queued(SetLocale::COOKIE));
    }

    public function test_validation_messages_are_translated(): void
    {
        Livewire::test(MoodComposer::class)
            ->set('phrase', 'ok')
            ->call('interpret')
            ->assertSee(trans('moodler.validation.phrase_min', ['min' => 3], 'it'));
    }
}
