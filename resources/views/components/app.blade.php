@props(['title' => null, 'width' => 'max-w-2xl'])

<x-layout :title="$title">
    <div class="mx-auto flex min-h-dvh {{ $width }} flex-col px-6">
        <header class="flex items-center gap-6 border-b border-neutral-200 py-5">
            <a href="{{ route('home') }}" @class([
                'text-sm tracking-tight transition',
                'font-medium text-black' => request()->routeIs('home'),
                'text-neutral-500 hover:text-black' => ! request()->routeIs('home'),
            ])>Chart</a>

            <a href="{{ route('changelog') }}" @class([
                'text-sm tracking-tight transition',
                'font-medium text-black' => request()->routeIs('changelog'),
                'text-neutral-500 hover:text-black' => ! request()->routeIs('changelog'),
            ])>Changelog</a>

            <a href="{{ route('forum') }}" @class([
                'text-sm tracking-tight transition',
                'font-medium text-black' => request()->routeIs('forum*'),
                'text-neutral-500 hover:text-black' => ! request()->routeIs('forum*'),
            ])>Forum</a>

            {{-- Anyone can read the site; what is listed here is what you can act on. --}}
            @auth
                <a href="{{ route('reports') }}" @class([
                    'text-sm tracking-tight transition',
                    'font-medium text-black' => request()->routeIs('reports*'),
                    'text-neutral-500 hover:text-black' => ! request()->routeIs('reports*'),
                ])>Reports</a>

                @if (auth()->user()->isAdmin())
                    <a href="{{ route('merge') }}" @class([
                        'text-sm tracking-tight transition',
                        'font-medium text-black' => request()->routeIs('merge*'),
                        'text-neutral-500 hover:text-black' => ! request()->routeIs('merge*'),
                    ])>Merge</a>
                @endif

                <a href="{{ route('profile') }}" @class([
                    'text-sm tracking-tight transition',
                    'font-medium text-black' => request()->routeIs('profile'),
                    'text-neutral-500 hover:text-black' => ! request()->routeIs('profile'),
                ])>Profile</a>
            @else
                <a href="{{ route('login') }}" @class([
                    'text-sm tracking-tight transition',
                    'font-medium text-black' => request()->routeIs('login'),
                    'text-neutral-500 hover:text-black' => ! request()->routeIs('login'),
                ])>Login</a>
            @endauth
        </header>

        <main class="flex-1 py-16">
            <x-flash />

            {{ $slot }}
        </main>
    </div>
</x-layout>
