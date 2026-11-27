<x-auth-card :title="__('moodler.auth.forgot_title')" :subtitle="__('moodler.auth.forgot_subtitle')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-text-field name="email" type="email" :label="__('moodler.auth.email')" autocomplete="email" required autofocus />

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-ink px-5 py-3 font-semibold text-white transition hover:bg-ink/85">{{ __('moodler.auth.send_reset_link') }}</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink/60">
        <a href="{{ route('login') }}" class="text-lilac-700 underline-offset-4 hover:underline">{{ __('moodler.auth.back_to_login') }}</a>
    </p>
</x-auth-card>
