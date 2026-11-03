<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::resource('branches', StoreController::class);
        Route::prefix('branches')->name('branches.')->group(function () {
            Route::post('refresh-status', [StoreController::class, 'refreshStatus'])
                ->name('refresh-status');
            Route::post('{store}/ping', [StoreController::class, 'pingNow'])
                ->name('ping');
        });
        // routes/web.php — dentro de tu grupo {current_team} existente

        Route::prefix('dev')->name('dev.')->group(function () {
            Route::prefix('projects')->name('projects.')->group(function () {
                Route::get('/', [ProjectController::class, 'index'])->name('index');
                Route::post('/', [ProjectController::class, 'store'])->name('store');
                Route::get('{project}', [ProjectController::class, 'show'])->name('show');
                Route::patch('{project}', [ProjectController::class, 'update'])->name('update');
                Route::delete('{project}', [ProjectController::class, 'destroy'])->name('destroy');
                Route::patch('{project}/status', [ProjectController::class, 'updateStatus'])->name('update-status');
            });

            Route::post('projects/{project}/tasks', [KanbanController::class, 'storeTask'])->name('tasks.store');
            Route::post('projects/{project}/columns', [KanbanController::class, 'storeColumn'])->name('columns.store');
            Route::patch('tasks/{task}/move', [KanbanController::class, 'moveTask'])->name('tasks.move');
            Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
            Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
            Route::patch('tasks/{task}/archive', [TaskController::class, 'archive'])->name('tasks.archive');
            Route::patch('tasks/{task}/restore', [TaskController::class, 'restore'])->name('tasks.restore');
        });

        Route::resource('tickets', TicketController::class)->names('tickets');
        Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
