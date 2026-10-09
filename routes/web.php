<?php

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketAssignmentController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketDecisionController;
use App\Http\Controllers\TicketProgressController;
use App\Http\Controllers\TicketResolutionController;
use App\Http\Controllers\TicketUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});



Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'cache.headers:no_store'])->name('dashboard');

Route::middleware(['auth', 'cache.headers:no_store'])->group(function () {
    // Staff can only change their own password here (PUT /password, routes/auth.php). Name, email and
    // the account itself are managed by an Admin (Admin → Users).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');

    // Flow 10: the bell polls /feed; the page lists everything. Only the user's own notifications.
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])
        ->middleware('throttle:120,1')->name('notifications.feed');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])
        ->whereUuid('notification')->name('notifications.open');

    // Admin → Users, All tickets (UserPolicy / TicketPolicy: "manage users") and System settings
    // (SettingPolicy: "manage settings"). Admin only.
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::put('/users/{user}/status', [AdminUserController::class, 'updateStatus'])
            ->whereNumber('user')->name('users.status');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])
            ->whereNumber('user')->name('users.destroy');
        Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings/auto-assign', [SettingsController::class, 'updateAutoAssign'])->name('settings.auto-assign');
        Route::put('/support-staff/{supportUser}/availability', [SettingsController::class, 'updateAvailability'])
            ->whereNumber('supportUser')->name('support.availability');
    });

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

        // Flows 5–6: Head of Service Management (resolve is shared with Flow 7, the assigned support person).
        Route::post('/tickets/{ticket}/assign', [TicketAssignmentController::class, 'assign'])->name('tickets.assign');
        Route::post('/tickets/{ticket}/reassign', [TicketAssignmentController::class, 'reassign'])->name('tickets.reassign');
        Route::post('/tickets/{ticket}/resolve', [TicketResolutionController::class, 'store'])->name('tickets.resolve');

        // Flow 7: the assigned Application Support person starts work.
        Route::post('/tickets/{ticket}/start', [TicketProgressController::class, 'store'])->name('tickets.start');

        // Files for the resolve form (FilePond), allowed only to whoever may resolve this ticket.
        Route::post('/tickets/{ticket}/resolution-uploads', [TicketUploadController::class, 'storeForResolution'])
            ->middleware('throttle:30,1')->name('tickets.resolution-uploads.store');
        Route::delete('/tickets/{ticket}/resolution-uploads', [TicketUploadController::class, 'destroyForResolution'])
            ->name('tickets.resolution-uploads.destroy');

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
