<x-app title="{{ $thread->title() }}" width="max-w-3xl">
    <a href="{{ route('forum') }}" class="text-xs text-neutral-500 underline underline-offset-4 transition hover:text-black hover:no-underline">
        ← Forum
    </a>

    <div class="mt-6">
        <x-post
            :post="$thread"
            :numbers="$numbers"
            :subject="$thread->subject"
            op
            :reply-url="route('forum.thread', [$thread, 'reply' => \App\Models\ForumThread::ANCHOR]).'#reply'"
        />
    </div>

    @if ($posts->isEmpty())
        <p class="mt-6 text-sm text-neutral-500">No replies yet.</p>
    @else
        <div class="mt-1 ml-6 space-y-1">
            @foreach ($posts as $post)
                <x-post
                    :post="$post"
                    :number="$post->number"
                    :numbers="$numbers"
                    :parent="$post->parent?->number"
                    :reply-url="route('forum.thread', [$thread, 'reply' => $post->number]).'#reply'"
                />
            @endforeach
        </div>
    @endif

    @guest
        <p id="reply" class="mt-8 text-xs text-neutral-500">
            <a href="{{ route('login') }}" class="underline underline-offset-4 hover:no-underline">Sign in</a>
            to reply.
        </p>
    @endguest

    {{-- Folded away until a reply is being written: clicking [Reply] on a post, or
         a failed submission, opens it. --}}
    @auth
    <details id="reply" class="mt-8" @if ($replyTo || $errors->any()) open @endif>
        <summary class="inline-block cursor-pointer text-xs text-neutral-500 underline underline-offset-4 transition hover:text-black hover:no-underline">
            [ Post a reply ]
        </summary>

        <div class="mt-3">
            @if ($replyTo)
                <p class="mb-2 text-[11px] text-neutral-500">
                    Replying to
                    <a href="#{{ $replyTo['anchor'] }}" class="underline underline-offset-2 hover:no-underline">{{ $replyTo['label'] }}</a>
                    ·
                    <a href="{{ route('forum.thread', $thread) }}#reply" class="underline underline-offset-2 hover:no-underline">cancel</a>
                </p>
            @endif

            <x-post-form
                :action="route('forum.reply', $thread)"
                :parent="$replyTo['parent_id'] ?? null"
                :body="$replyTo ? $replyTo['quote'].PHP_EOL : ''"
                submit="Reply"
            />
        </div>
    </details>
    @endauth
</x-app>
