<?php

use App\Http\Controllers\Admin\GoogleDriveOAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }

    return view('landing');
})->name('home');
Route::view('/login', 'admin.auth.login')->name('login');

Route::get('/callback', [GoogleDriveOAuthController::class, 'callback'])->name('google-drive.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
