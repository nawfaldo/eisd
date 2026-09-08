<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\CorruptionController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MergeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

// The data, the board, and everything that only reads them is open to anyone.
Route::get('/', HomeController::class)->name('home');
Route::get('/corruption', CorruptionController::class)->name('corruption');
Route::get('/changelog', ChangelogController::class)->name('changelog');
Route::get('/forum', [ForumController::class, 'index'])->name('forum');
Route::get('/forum/{thread}', [ForumController::class, 'show'])->name('forum.thread');
Route::get('/provinces/{region}', ProvinceController::class)->name('province');

// Writing anything, and anything about your own account, needs a sign-in.
Route::middleware('auth')->group(function () {
    Route::post('/forum', [ForumController::class, 'store'])->name('forum.store');
    Route::post('/forum/{thread}', [ForumController::class, 'reply'])->name('forum.reply');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::middleware('admin')->group(function () {
        Route::get('/merge', [MergeController::class, 'index'])->name('merge');
        Route::post('/merge/{report}', [MergeController::class, 'merge'])->name('merge.merge');
        Route::post('/merge/{report}/decline', [MergeController::class, 'decline'])->name('merge.decline');
    });

    Route::get('/profile', ProfileController::class)->name('profile');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
