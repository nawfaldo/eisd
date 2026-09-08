{{-- One place every page's confirmation is shown, so a controller only has to
     flash a message for it to appear. --}}
@if (session('status'))
    <p role="status" class="mb-8 border-l-2 border-black pl-3 text-xs text-neutral-600">
        {{ session('status') }}
    </p>
@endif
