@props([
    'action',
    'subject' => false,
    'parent' => null,
    'body' => '',
    'submit' => 'Post',
])

{{-- The one form the board uses: text, an optional link, an optional file. --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="border border-black p-3">
    @csrf

    @if ($parent)
        <input type="hidden" name="parent_id" value="{{ $parent }}">
    @endif

    @if ($subject)
        <input type="text" name="subject" value="{{ old('subject') }}" maxlength="255" placeholder="Subject (optional)"
               class="block w-full border border-neutral-300 bg-white px-2 py-1.5 text-sm outline-none transition placeholder:text-neutral-400 focus:border-black">
    @endif

    <textarea name="body" rows="5" required placeholder="Comment"
              @class([
                  'block w-full border bg-white px-2 py-1.5 text-sm outline-none transition placeholder:text-neutral-400 focus:border-black',
                  'mt-2' => $subject,
                  'border-black' => $errors->has('body'),
                  'border-neutral-300' => ! $errors->has('body'),
              ])>{{ old('body', $body) }}</textarea>

    <div class="mt-2 flex flex-wrap items-center gap-3">
        <input type="file" name="media" accept="image/*,video/*"
               class="max-w-full text-xs text-neutral-500 file:mr-2 file:cursor-pointer file:border file:border-neutral-300 file:bg-white file:px-2 file:py-1 file:text-xs file:text-black hover:file:border-black">

        <button type="submit"
                class="ml-auto border border-black bg-black px-3 py-1.5 text-xs font-medium tracking-tight text-white transition hover:bg-white hover:text-black">
            {{ $submit }}
        </button>
    </div>

    @if ($errors->any())
        <ul class="mt-2 space-y-0.5 text-xs text-black">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif
</form>
