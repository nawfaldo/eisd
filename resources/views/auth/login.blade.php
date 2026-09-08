<x-layout title="Sign in">
    <main class="grid min-h-dvh place-items-center px-6 py-16">
        <div class="w-full max-w-xs">
            <h1 class="text-xl font-medium tracking-tight">Sign in</h1>
            <p class="mt-1 text-sm text-neutral-500">Use your name and password.</p>

            <div class="mt-6 empty:mt-0">
                <x-flash />
            </div>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf

                <x-field name="name" label="Name" autocomplete="username" autofocus required />
                <x-field name="password" label="Password" type="password" autocomplete="current-password" required />

                <x-button>Sign in</x-button>
            </form>

            <p class="mt-8 border-t border-neutral-200 pt-5 text-xs text-neutral-500">
                No account?
                <a href="{{ route('register') }}" class="text-black underline underline-offset-4 hover:no-underline">Register</a>
            </p>
        </div>
    </main>
</x-layout>
