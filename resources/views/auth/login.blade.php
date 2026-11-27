<x-auth-card :title="__('moodler.auth.login_title')" :subtitle="__('moodler.auth.login_subtitle')">
    <x-social-buttons />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-text-field name="email" type="email" :label="__('moodler.auth.email')" autocomplete="email" required autofocus />
        <x-text-field name="password" type="password" :label="__('moodler.auth.password')" autocomplete="current-password" required />

        <div class="flex items-center justify-between gap-3 text-sm">
            <label class="flex items-center gap-2 text-ink/65">
                <input type="checkbox" name="remember" class="rounded border-ink/15 bg-canvas text-lilac-700 focus:ring-lilac-300/50" @checked(old('remember'))>
                {{ __('moodler.auth.remember') }}
            </label>
            <a href="{{ route('password.request') }}" class="text-lilac-700 underline-offset-4 hover:underline">{{ __('moodler.auth.forgot') }}</a>
        </div>

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-ink px-5 py-3 font-semibold text-white transition hover:bg-ink/85">{{ __('moodler.auth.login') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink/60">
        {{ __('moodler.auth.no_account') }}
        <a href="{{ route('register') }}" class="text-lilac-700 underline-offset-4 hover:underline">{{ __('moodler.auth.register') }}</a>
    </p>
</x-auth-card>
