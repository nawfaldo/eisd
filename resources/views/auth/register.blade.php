<x-layout title="Register">
    <main class="grid min-h-dvh place-items-center px-6 py-16">
        <div class="w-full max-w-xs">
            <h1 class="text-xl font-medium tracking-tight">Register</h1>
            <p class="mt-1 text-sm text-neutral-500">Pick a name and a password.</p>

            <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
                @csrf

                <x-field name="name" label="Name" autocomplete="username" autofocus required />
                <x-field name="password" label="Password" type="password" autocomplete="new-password" required />

                <x-button>Create account</x-button>
            </form>

            <p class="mt-8 border-t border-neutral-200 pt-5 text-xs text-neutral-500">
                Already registered?
                <a href="{{ route('login') }}" class="text-black underline underline-offset-4 hover:no-underline">Sign in</a>
            </p>
        </div>
    </main>
</x-layout>
