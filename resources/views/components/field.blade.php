@props(['name', 'label', 'type' => 'text'])

<div>
    <label for="{{ $name }}" class="block text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">
        {{ $label }}
    </label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name) }}" @endif
        {{ $attributes->merge([
            'class' => 'mt-2 block w-full border bg-white px-3 py-2.5 text-sm text-black outline-none transition placeholder:text-neutral-400 focus:border-black focus:ring-1 focus:ring-black '
                .($errors->has($name) ? 'border-black ring-1 ring-black' : 'border-neutral-300'),
        ]) }}
    >

    @error($name)
        <p class="mt-2 text-xs text-black">{{ $message }}</p>
    @enderror
</div>
