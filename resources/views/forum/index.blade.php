<x-app title="Forum" width="max-w-3xl">
    {{-- Reading the board needs nothing; writing to it needs an account. --}}
    @auth
        {{-- The form stays out of the way until it is asked for, or a submission fails. --}}
        <details @if ($errors->any()) open @endif>
            <summary class="inline-block cursor-pointer text-xs text-neutral-500 underline underline-offset-4 transition hover:text-black hover:no-underline">
                [ Start a new thread ]
            </summary>

            <div class="mt-3">
                <x-post-form :action="route('forum.store')" subject submit="Start thread" />
            </div>
        </details>
    @else
        <p class="text-xs text-neutral-500">
            <a href="{{ route('login') }}" class="underline underline-offset-4 hover:no-underline">Sign in</a>
            to start a thread or reply.
        </p>
    @endauth

    @if ($threads->isEmpty())
        <p class="mt-10 text-sm text-neutral-500">No threads yet.</p>
    @else
        <div class="mt-10 space-y-8">
            @foreach ($threads as $thread)
                <section>
                    <x-post
                        :post="$thread"
                        :subject="$thread->subject"
                        op
                        :reply-url="route('forum.thread', $thread)"
                    />

                    <p class="mt-1 ml-6 text-[11px] text-neutral-500">
                        <a href="{{ route('forum.thread', $thread) }}" class="underline underline-offset-4 hover:no-underline">
                            {{ $thread->posts_count === 1 ? '1 reply' : number_format($thread->posts_count).' replies' }}
                        </a>

                        <span class="text-neutral-400">· last activity {{ $thread->bumped_at->diffForHumans() }}</span>
                    </p>
                </section>
            @endforeach
        </div>

        <x-pager :paginator="$threads" />
    @endif
</x-app>
