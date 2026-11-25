<x-auth-card :title="__('moodler.auth.register_title')" :subtitle="__('moodler.auth.register_subtitle')">
    <x-social-buttons />

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <x-text-field name="name" :label="__('moodler.auth.name')" autocomplete="name" required autofocus />
        <x-text-field name="email" type="email" :label="__('moodler.auth.email')" autocomplete="email" required />
        <x-text-field name="password" type="password" :label="__('moodler.auth.password')" autocomplete="new-password" required />
        <x-text-field name="password_confirmation" type="password" :label="__('moodler.auth.password_confirmation')" autocomplete="new-password" required />

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-ember-400 px-5 py-3 font-semibold text-ink-950 transition hover:bg-ember-300">{{ __('moodler.auth.register') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-white/55">
        {{ __('moodler.auth.have_account') }}
        <a href="{{ route('login') }}" class="text-ember-300 underline-offset-4 hover:underline">{{ __('moodler.auth.login') }}</a>
    </p>
</x-auth-card>
