{{-- Google and Apple sign-in, each shown only once its credentials are configured. --}}
@php($providers = array_filter(\App\Http\Controllers\SocialLoginController::PROVIDERS, \App\Http\Controllers\SocialLoginController::isEnabled(...)))

@if ($providers !== [])
    <div class="space-y-2">
        @foreach ($providers as $provider)
            <a href="{{ route('social.redirect', $provider) }}" class="flex w-full items-center justify-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-white/10">
                @if ($provider === 'google')
                    <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="#EA4335" d="M12 10.2v3.9h5.5c-.24 1.4-1.66 4.1-5.5 4.1-3.3 0-6-2.74-6-6.1s2.7-6.1 6-6.1c1.88 0 3.14.8 3.86 1.49l2.63-2.54C16.8 3.4 14.6 2.4 12 2.4 6.7 2.4 2.4 6.7 2.4 12s4.3 9.6 9.6 9.6c5.54 0 9.2-3.9 9.2-9.38 0-.63-.07-1.11-.15-1.6H12Z"/></svg>
                @else
                    <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.37 12.6c-.02-2.2 1.8-3.26 1.88-3.31-1.03-1.5-2.62-1.7-3.18-1.73-1.35-.14-2.64.8-3.33.8-.69 0-1.74-.78-2.86-.76-1.47.02-2.83.86-3.59 2.18-1.53 2.65-.39 6.58 1.1 8.73.73 1.05 1.6 2.23 2.73 2.19 1.1-.04 1.51-.71 2.84-.71 1.32 0 1.7.71 2.86.69 1.18-.02 1.93-1.07 2.65-2.13.84-1.22 1.18-2.4 1.2-2.46-.03-.01-2.3-.88-2.32-3.5ZM14.2 6.13c.6-.73 1.01-1.75.9-2.76-.87.04-1.92.58-2.54 1.31-.56.65-1.05 1.68-.92 2.67.97.08 1.96-.49 2.56-1.22Z"/></svg>
                @endif
                {{ __("moodler.auth.continue_with_{$provider}") }}
            </a>
        @endforeach
    </div>

    <div class="my-5 flex items-center gap-3 text-xs text-white/35">
        <span class="h-px flex-1 bg-white/10"></span>
        {{ __('moodler.auth.or') }}
        <span class="h-px flex-1 bg-white/10"></span>
    </div>
@endif
