<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Sign in with Google or Apple. A returning person is found by their provider id, or by
 * their email the first time they use a provider, so accounts are never duplicated.
 */
class SocialLoginController extends Controller
{
    public const PROVIDERS = ['google', 'apple'];

    /**
     * Whether a provider is supported and has credentials.
     */
    public static function isEnabled(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true) && filled(config("services.{$provider}.client_id"));
    }

    public function redirect(string $provider): SymfonyRedirect
    {
        abort_unless(self::isEnabled($provider), 404);

        $driver = Socialite::driver($provider);

        return $provider === 'apple'
            ? $driver->scopes(['name', 'email'])->redirect()
            : $driver->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(self::isEnabled($provider), 404);

        try {
            // Apple posts back cross-site, so the session (and its state) is not available.
            $socialUser = $provider === 'apple'
                ? Socialite::driver($provider)->stateless()->user()
                : Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors(['email' => __('moodler.auth.social_failed')]);
        }

        $user = $this->findOrCreateUser($provider, $socialUser);

        if ($user === null) {
            return redirect()->route('login')->withErrors(['email' => __('moodler.auth.social_no_email')]);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    private function findOrCreateUser(string $provider, SocialUser $socialUser): ?User
    {
        $column = "{$provider}_id";
        $user = User::where($column, $socialUser->getId())->first();

        if ($user !== null) {
            return $user;
        }

        $email = $socialUser->getEmail();

        if (blank($email)) {
            return null;
        }

        $user = User::where('email', $email)->first();

        if ($user !== null) {
            $user->forceFill([$column => $socialUser->getId(), 'email_verified_at' => $user->email_verified_at ?? now()])->save();

            return $user;
        }

        return User::create([
            'name' => $socialUser->getName() ?: Str::before($email, '@'),
            'email' => $email,
            $column => $socialUser->getId(),
            'email_verified_at' => now(),
        ]);
    }
}
