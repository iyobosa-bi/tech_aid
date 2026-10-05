<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketDecisionController;
use App\Http\Controllers\TicketUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});



Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'cache.headers:no_store'])->name('dashboard');

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

    // One ticket. {ticket} is numeric so words like "create" never reach these routes.
    Route::whereNumber('ticket')->group(function () {
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
        Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');

        Route::post('/tickets/{ticket}/approve', [TicketDecisionController::class, 'approve'])->name('tickets.approve');
        Route::post('/tickets/{ticket}/decline', [TicketDecisionController::class, 'decline'])->name('tickets.decline');

        Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])
            ->middleware('throttle:20,1')->name('tickets.comments.store');

        // Scoped: the attachment must belong to this ticket, or it's a 404.
        Route::scopeBindings()->group(function () {
            Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'show'])
                ->name('tickets.attachments.show');
            Route::get('/tickets/{ticket}/attachments/{attachment}/download', [TicketAttachmentController::class, 'download'])
                ->name('tickets.attachments.download');
        });
    });
});

require __DIR__.'/auth.php';

// Any URL no route above matched. Going through a route (rather than letting the router throw
// its own 404) runs the web middleware first, so the 404 page knows whether someone is signed
// in and can offer "Back to dashboard" or "Go to sign in". See resources/views/errors/404.blade.php.
Route::fallback(fn () => abort(404));
