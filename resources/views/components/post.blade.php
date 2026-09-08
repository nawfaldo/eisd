@props([
    'post',
    'number' => null,
    'numbers' => [],
    'op' => false,
    'subject' => null,
    'replyUrl' => null,
    'parent' => null,
])

@php
    // The opening post is the thread, so it is quoted as OP rather than by a number.
    $anchor = $op ? \App\Models\ForumThread::ANCHOR : 'p'.$number;
@endphp

{{-- One post on the board: who wrote it, what it carries, and what it says. --}}
<article id="{{ $anchor }}" {{ $attributes->merge([
    'class' => 'post border border-neutral-300 p-3 '.($op ? 'bg-white' : 'bg-neutral-50'),
]) }}>
    <header class="flex flex-wrap items-baseline gap-x-2 gap-y-1 text-[11px] text-neutral-500">
        @if ($subject)
            <span class="font-medium text-black">{{ $subject }}</span>
        @endif

        <span class="font-medium text-black">{{ $post->author?->name ?? 'Anonymous' }}</span>

        <time datetime="{{ $post->created_at->toIso8601String() }}" class="tabular-nums">
            {{ $post->created_at->format('j M Y, H:i') }}
        </time>

        <a href="#{{ $anchor }}" class="tabular-nums transition hover:text-black">
            {{ $op ? 'OP' : 'No.'.$number }}
        </a>

        @if ($parent)
            <a href="#p{{ $parent }}" class="tabular-nums underline underline-offset-2 hover:no-underline">
                &gt;&gt;{{ $parent }}
            </a>
        @endif

        {{-- Only offered to someone who can actually post the reply. --}}
        @auth
            @if ($replyUrl)
                <a href="{{ $replyUrl }}" class="ml-auto transition hover:text-black">[Reply]</a>
            @endif
        @endauth
    </header>

    @if ($post->hasMedia())
        <figure class="mt-2">
            @if ($post->isImage())
                <a href="{{ $post->mediaUrl() }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $post->mediaUrl() }}" alt="" loading="lazy"
                         class="max-h-80 border border-neutral-300 object-contain">
                </a>
            @elseif ($post->isVideo())
                <video src="{{ $post->mediaUrl() }}" controls preload="metadata"
                       class="max-h-80 border border-neutral-300"></video>
            @else
                {{-- Anything the browser cannot play is still offered as a file. --}}
                <a href="{{ $post->mediaUrl() }}" target="_blank" rel="noopener noreferrer"
                   class="text-xs underline underline-offset-2 hover:no-underline">{{ $post->mediaName() }}</a>
            @endif
        </figure>
    @endif

    <div class="mt-2 text-sm leading-relaxed break-words">{!! \App\Support\PostText::render($post->body, $numbers) !!}</div>
</article>
