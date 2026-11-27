@props([
    'name',
    'label',
    'type' => 'text',
])

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-ink/80">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $attributes->get('value')) }}" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->except('value')->class('w-full rounded-xl border border-ink/10 bg-white/80 px-4 py-2.5 text-ink placeholder:text-ink/40 transition focus:border-ink focus:ring-2 focus:ring-lilac-300/50 focus:outline-none aria-invalid:border-red-400') }}
    >
    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
