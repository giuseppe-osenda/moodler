<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language: the player's saved choice first, then the
 * browser's preferred languages, then the app default (Italian).
 */
class SetLocale
{
    public const COOKIE = 'locale';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(self::resolve($request));

        return $next($request);
    }

    public static function resolve(Request $request): string
    {
        $chosen = $request->cookie(self::COOKIE);

        if (is_string($chosen) && self::isSupported($chosen)) {
            return $chosen;
        }

        foreach ($request->getLanguages() as $language) {
            $primary = strtolower(strtok(str_replace('-', '_', $language), '_'));

            if (self::isSupported($primary)) {
                return $primary;
            }
        }

        return (string) config('app.locale');
    }

    public static function isSupported(string $locale): bool
    {
        return array_key_exists($locale, config('app.supported_locales', []));
    }
}
