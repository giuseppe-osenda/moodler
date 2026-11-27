<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['legal.operator' => 'Mario Rossi', 'legal.email' => 'privacy@moodler.test']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        return ['it' => ['it'], 'en' => ['en'], 'es' => ['es'], 'fr' => ['fr'], 'de' => ['de']];
    }

    #[DataProvider('locales')]
    public function test_the_privacy_policy_names_the_operator_and_the_youtube_and_google_policies(string $locale): void
    {
        $this->withCookie('locale', $locale)->get(route('privacy'))
            ->assertOk()
            ->assertSee(trans('legal.privacy.title', [], $locale))
            ->assertSee('Mario Rossi')
            ->assertSee('href="mailto:privacy@moodler.test"', false)
            ->assertSee('href="https://www.youtube.com/t/terms"', false)
            ->assertSee('href="https://policies.google.com/privacy"', false)
            ->assertDontSee(':email');
    }

    #[DataProvider('locales')]
    public function test_the_terms_explain_the_subscription_and_refunds(string $locale): void
    {
        $this->withCookie('locale', $locale)->get(route('terms'))
            ->assertOk()
            ->assertSee(trans('legal.terms.title', [], $locale))
            ->assertSee(trans('moodler.checkout.price', [], $locale))
            ->assertSee('href="https://paddle.net"', false)
            ->assertDontSee(':price');
    }

    public function test_every_page_links_the_policies_and_sign_up_asks_to_accept_them(): void
    {
        $this->get(route('terms'))->assertSee(route('privacy'))->assertSee(route('terms'));

        $this->withCookie('locale', 'it')->get(route('register'))
            ->assertSee('<a href="'.route('terms').'">Termini</a>', false)
            ->assertSee('<a href="'.route('privacy').'">Informativa privacy</a>', false);
    }

    public function test_search_engines_are_asked_not_to_index_the_site_until_it_is_switched_on(): void
    {
        $this->get(route('privacy'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        config(['app.indexable' => true]);

        $this->get(route('privacy'))->assertHeaderMissing('X-Robots-Tag');
    }
}
