<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\GoogleDriveOAuthController;
use App\Http\Controllers\Admin\AdminQuizController;
use App\Http\Controllers\Admin\AdminQuizTemplateController;
use App\Http\Controllers\Admin\AdminGenerateLinkController;
use App\Http\Controllers\Admin\AdminQuizLinkController;
use App\Http\Controllers\Admin\AdminResultController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard')->name('home');

    Route::view('/login', 'admin.auth.login')->middleware('guest')->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('guest')->name('login.store');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/integrations/google-drive/connect', [GoogleDriveOAuthController::class, 'redirect'])->name('integrations.google-drive.connect');

        Route::get('/quizzes', [AdminQuizController::class, 'index'])->name('quizzes.index');
        Route::get('/quizzes/template', AdminQuizTemplateController::class)->name('quizzes.template');
        Route::get('/quizzes/create', [AdminQuizController::class, 'create'])->name('quizzes.create');
        Route::get('/quizzes/{quiz}', [AdminQuizController::class, 'show'])->name('quizzes.show');
        Route::get('/quizzes/{quiz}/edit', [AdminQuizController::class, 'edit'])->name('quizzes.edit');
        Route::delete('/quizzes/{quiz}', [AdminQuizController::class, 'destroy'])->name('quizzes.destroy');

        Route::get('/generate-link', [AdminGenerateLinkController::class, 'index'])->name('links.generate');
        Route::post('/generate-link', [AdminGenerateLinkController::class, 'store'])->name('links.generate.store');

        Route::get('/links', [AdminQuizLinkController::class, 'index'])->name('links.index');
        Route::get('/links/{quizLink}', [AdminQuizLinkController::class, 'show'])->name('links.show');

        Route::get('/results', [AdminResultController::class, 'index'])->name('results.index');
        Route::get('/results/export', [AdminResultController::class, 'export'])->name('results.export');
        Route::get('/results/{quizResult}', [AdminResultController::class, 'show'])->name('results.show');

        Route::middleware('super_admin')->group(function () {
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        });
    });
});

Route::view('/quiz/{token}', 'participant.start')->name('participant.quiz.start');
Route::view('/quiz/{token}/work', 'participant.work')->name('participant.quiz.work');
Route::view('/quiz/{token}/done', 'participant.done')->name('participant.quiz.done');
