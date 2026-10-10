<?php
use App\Http\Controllers\AuthController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'show'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:5,1']); // 5 tries a minute
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => view('home'));

    // Example of a role-limited area. Add real pages like this as they are built:
    // Route::get('/payments/new', ...)->middleware(RoleMiddleware::class.':Bursar');
    // Route::get('/audit', ...)->middleware(RoleMiddleware::class.':Bursar,Director,Deputy director,Auditor');
});
