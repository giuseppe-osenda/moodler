<x-auth-card :title="__('moodler.auth.reset_title')">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-text-field name="email" type="email" :label="__('moodler.auth.email')" :value="$request->email" autocomplete="email" required />
        <x-text-field name="password" type="password" :label="__('moodler.auth.new_password')" autocomplete="new-password" required autofocus />
        <x-text-field name="password_confirmation" type="password" :label="__('moodler.auth.password_confirmation')" autocomplete="new-password" required />

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-ink px-5 py-3 font-semibold text-white transition hover:bg-ink/85">{{ __('moodler.auth.reset') }}</button>
    </form>
</x-auth-card>
