<x-app title="Profile">
    <dl class="space-y-8">
        <div>
            <dt class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Name</dt>
            <dd class="mt-2 text-lg tracking-tight">{{ $user->name }}</dd>
        </div>

        <div>
            <dt class="text-[11px] font-medium uppercase tracking-[0.15em] text-neutral-500">Role</dt>
            <dd class="mt-2 text-lg tracking-tight">{{ $user->role->label() }}</dd>
        </div>
    </dl>

    <form method="POST" action="{{ route('logout') }}" class="mt-12 border-t border-neutral-200 pt-6">
        @csrf
        <button type="submit" class="text-xs text-neutral-500 underline underline-offset-4 transition hover:text-black hover:no-underline">
            Sign out
        </button>
    </form>
</x-app>
