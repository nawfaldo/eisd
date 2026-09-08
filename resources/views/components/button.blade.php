<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'w-full border border-black bg-black px-3 py-2.5 text-sm font-medium text-white transition hover:bg-white hover:text-black focus:outline-none focus:ring-1 focus:ring-black focus:ring-offset-2',
]) }}>
    {{ $slot }}
</button>
