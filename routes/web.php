<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'cache.headers:no_store'])->name('dashboard');

Route::middleware(['auth', 'cache.headers:no_store'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');

    Route::post('/tickets/uploads', [TicketUploadController::class, 'store'])
        ->middleware('throttle:30,1')->name('tickets.uploads.store');
    Route::delete('/tickets/uploads', [TicketUploadController::class, 'destroy'])
        ->name('tickets.uploads.destroy');
});

require __DIR__.'/auth.php';
