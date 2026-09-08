<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users,name'],
            'password' => ['required', 'string'],
        ]);

        $user = User::create([
            ...$validated,
            'role' => UserRole::Normal,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('home')->with('status', 'Welcome, '.$user->name.'. Your account is ready.');
    }
}
