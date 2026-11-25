<?php

namespace App\Providers;

use App\Services\OpenAiClient;
use App\Services\SpotifyClient;
use App\Services\YouTubeClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OpenAiClient::class, fn (): OpenAiClient => new OpenAiClient(
            apiKey: config('services.openai.key'),
            model: (string) config('services.openai.model'),
            baseUrl: (string) config('services.openai.base_url'),
        ));

        $this->app->singleton(SpotifyClient::class, fn (): SpotifyClient => new SpotifyClient(
            clientId: config('services.spotify.client_id'),
            clientSecret: config('services.spotify.client_secret'),
            market: (string) config('services.spotify.market'),
            apiUrl: (string) config('services.spotify.api_url'),
            accountsUrl: (string) config('services.spotify.accounts_url'),
        ));

        $this->app->singleton(YouTubeClient::class, fn (): YouTubeClient => new YouTubeClient(
            apiKey: config('services.youtube.key'),
            apiUrl: (string) config('services.youtube.api_url'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SocialiteWasCalled::class, [AppleExtendSocialite::class, 'handle']);
    }
}
