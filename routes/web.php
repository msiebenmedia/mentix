<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\QuestionCatalogController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizController;
use App\Http\Controllers\Admin\QuizHostController;
use App\Http\Controllers\QuizPlayerController;
use App\Http\Controllers\StreamController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => view('welcome'));

Route::get('/stream/{quiz}', [StreamController::class, 'show'])->name('stream.show');
Route::get('/stream/{quiz}/state', [StreamController::class, 'state'])->name('stream.state');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/quizzes/{quiz}/play', [QuizPlayerController::class, 'show'])->name('quizzes.play');
    Route::post('/quizzes/{quiz}/answer', [QuizPlayerController::class, 'submitAnswer'])->name('quizzes.answer');

});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // User Management
    Route::get('/users/roles', [UserRoleController::class, 'index'])->name('users.roles.index');
    Route::post('/users/{user}/roles', [UserRoleController::class, 'update'])->name('users.roles.update');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
    Route::post('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggle-active');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

    // Question Management
    Route::resource('question-catalogs', QuestionCatalogController::class);
    Route::resource('questions', QuestionController::class);

    // Quiz Management
    Route::resource('quizzes', QuizController::class);
    Route::put('quizzes/{quiz}/questions', [QuizController::class, 'updateQuestions'])->name('quizzes.questions.update');
    Route::post('quizzes/{quiz}/questions/shuffle', [QuizController::class, 'shuffleQuestions'])->name('quizzes.questions.shuffle');
    Route::post('quizzes/{quiz}/restart', [QuizController::class, 'restart'])->name('quizzes.restart');

    // Host Controls
    Route::get('quizzes/{quiz}/host', [QuizHostController::class, 'show'])->name('quizzes.host');
    Route::post('quizzes/{quiz}/start', [QuizHostController::class, 'start'])->name('quizzes.start');
    Route::post('quizzes/{quiz}/pause', [QuizHostController::class, 'pause'])->name('quizzes.pause');
    Route::post('quizzes/{quiz}/next-question', [QuizHostController::class, 'nextQuestion'])->name('quizzes.next-question');
    Route::post('quizzes/{quiz}/previous-question', [QuizHostController::class, 'previousQuestion'])->name('quizzes.previous-question');
    Route::post('quizzes/{quiz}/reveal-question', [QuizHostController::class, 'revealQuestion'])->name('quizzes.reveal-question');
    Route::post('quizzes/{quiz}/finish', [QuizHostController::class, 'finish'])->name('quizzes.finish');

});