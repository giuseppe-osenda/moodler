<x-auth-card :title="__('moodler.auth.forgot_title')" :subtitle="__('moodler.auth.forgot_subtitle')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-text-field name="email" type="email" :label="__('moodler.auth.email')" autocomplete="email" required autofocus />

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-ember-400 px-5 py-3 font-semibold text-ink-950 transition hover:bg-ember-300">{{ __('moodler.auth.send_reset_link') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-white/55">
        <a href="{{ route('login') }}" class="text-ember-300 underline-offset-4 hover:underline">{{ __('moodler.auth.back_to_login') }}</a>
    </p>
</x-auth-card>
