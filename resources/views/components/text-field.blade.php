@props([
    'name',
    'label',
    'type' => 'text',
])

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-white/80">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $attributes->get('value')) }}" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->except('value')->class('w-full rounded-xl border border-white/10 bg-ink-950/60 px-4 py-2.5 text-white placeholder:text-white/30 transition focus:border-ember-400 focus:ring-2 focus:ring-ember-400/30 focus:outline-none aria-invalid:border-red-400/60') }}
    >
    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm text-red-300">{{ $message }}</p>
    @enderror
</div>
