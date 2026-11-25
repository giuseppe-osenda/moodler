<x-auth-card :title="__('moodler.auth.verify.title')" :subtitle="__('moodler.auth.verify.subtitle', ['email' => auth()->user()->email])">
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-ember-400 px-5 py-3 font-semibold text-ink-950 transition hover:bg-ember-300">{{ __('moodler.auth.verify.resend') }}</button>
    </form>
</x-auth-card>
