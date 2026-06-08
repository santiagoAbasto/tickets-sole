<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AssignmentSettingController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\TicketAssignmentController;
use App\Http\Controllers\Admin\TicketAttachmentController;
use App\Http\Controllers\Admin\TicketCategoryController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TicketCredentialController;
use App\Http\Controllers\Admin\TicketDashboardController;
use App\Http\Controllers\Admin\TicketDelegationController;
use App\Http\Controllers\Admin\TicketMessageController;
use App\Http\Controllers\Admin\TicketNoteController;
use App\Http\Controllers\Admin\TicketPriorityController;
use App\Http\Controllers\Admin\TicketStatusController;
use App\Http\Controllers\Admin\TelegramAlertController;
use App\Http\Controllers\Admin\TicketWhatsappController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Portal\TicketController as PortalTicketController;
use App\Http\Controllers\Public\PublicTicketController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public support form — no authentication. Anyone can submit a ticket.
// ---------------------------------------------------------------------------
Route::get('/', [PublicTicketController::class, 'create'])->name('public.support.create');
Route::post('/', [PublicTicketController::class, 'store'])
    ->middleware('throttle:6,1')->name('public.support.store');
Route::get('gracias', [PublicTicketController::class, 'thanks'])->name('public.support.thanks');
Route::redirect('soporte', '/', 301);
Route::redirect('soporte/gracias', '/gracias', 301);

// Public ticket tracking by code + email (no account).
Route::get('seguimiento', [PublicTicketController::class, 'track'])->name('public.track.form');
Route::post('seguimiento', [PublicTicketController::class, 'lookup'])->middleware('throttle:10,1')->name('public.track.lookup');
Route::get('seguimiento/ticket', [PublicTicketController::class, 'tracked'])->name('public.track.show');
Route::get('seguimiento/ticket/messages', [PublicTicketController::class, 'trackedMessages'])->name('public.track.messages');
Route::post('seguimiento/ticket/responder', [PublicTicketController::class, 'trackedReply'])->middleware('throttle:15,1')->name('public.track.reply');

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('admin/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');
    Route::get('admin/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('admin/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:3,1')->name('password.email');
    Route::get('admin/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('admin/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.store');
    Route::redirect('login', '/admin/login', 301);
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

// ---------------------------------------------------------------------------
// Admin panel — staff only
// ---------------------------------------------------------------------------
Route::prefix('admin')->name('admin.')
    ->middleware(['auth', 'role:Super Admin|Admin|Agente|Diseñadora industrial'])
    ->group(function () {
        // Tickets
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('dashboard', [TicketDashboardController::class, 'index'])->name('dashboard');
            Route::get('/', [TicketController::class, 'index'])->name('index');
            Route::get('create', [TicketController::class, 'create'])->name('create');
            Route::post('/', [TicketController::class, 'store'])->name('store');
            Route::get('{ticket}', [TicketController::class, 'show'])->name('show');
            Route::put('{ticket}', [TicketController::class, 'update'])->name('update');
            Route::delete('{ticket}', [TicketController::class, 'destroy'])->name('destroy');

            Route::get('{ticket}/messages', [TicketMessageController::class, 'index'])->name('messages.index');
            Route::post('{ticket}/messages', [TicketMessageController::class, 'store'])->name('messages.store');
            Route::post('{ticket}/notes', [TicketNoteController::class, 'store'])->name('notes.store');
            Route::post('{ticket}/assign', [TicketAssignmentController::class, 'store'])->name('assign');
            Route::post('{ticket}/claim', [TicketController::class, 'claim'])->name('claim');
            Route::post('{ticket}/status', [TicketController::class, 'changeStatus'])->name('status');
            Route::post('{ticket}/attachments', [TicketAttachmentController::class, 'store'])->name('attachments.store');
            Route::delete('{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'destroy'])->name('attachments.destroy');
            Route::post('{ticket}/notify-customer', [TicketController::class, 'notifyCustomer'])->name('notify-customer');
            Route::put('{ticket}/credentials', [TicketCredentialController::class, 'update'])->name('credentials.update');
            // Delegation workflow: assignee requests → Super Admin/Admin reviews.
            Route::post('{ticket}/delegations', [TicketDelegationController::class, 'store'])->name('delegations.store');
            Route::post('{ticket}/delegations/{delegation}/approve', [TicketDelegationController::class, 'approve'])->name('delegations.approve');
            Route::post('{ticket}/delegations/{delegation}/reject', [TicketDelegationController::class, 'reject'])->name('delegations.reject');
            Route::delete('{ticket}/delegations/{delegation}', [TicketDelegationController::class, 'cancel'])->name('delegations.cancel');
            Route::put('{ticket}/whatsapp-number', [TicketWhatsappController::class, 'updateNumber'])->middleware('throttle:30,1')->name('whatsapp.number');
            Route::post('{ticket}/whatsapp/log', [TicketWhatsappController::class, 'log'])->middleware('throttle:30,1')->name('whatsapp.log');
        });

        // In-app notifications (bell)
        Route::get('notifications/ticket-alerts', [NotificationController::class, 'ticketAlerts'])->name('notifications.ticket-alerts');
        Route::get('notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
        Route::post('notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');

        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        // Default assignee (who every new ticket falls to) — Admin / Super Admin.
        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('assignment', [AssignmentSettingController::class, 'edit'])->name('assignment-settings.edit');
            Route::put('assignment', [AssignmentSettingController::class, 'update'])->name('assignment-settings.update');

            // Outgoing ticket alerts via Telegram — notify-only.
            Route::get('telegram-alerts', [TelegramAlertController::class, 'edit'])->name('telegram-alerts.edit');
            Route::put('telegram-alerts', [TelegramAlertController::class, 'update'])->name('telegram-alerts.update');
            Route::post('telegram-alerts/test', [TelegramAlertController::class, 'test'])->middleware('throttle:6,1')->name('telegram-alerts.test');
            Route::post('telegram-alerts/detect', [TelegramAlertController::class, 'detect'])->middleware('throttle:6,1')->name('telegram-alerts.detect');
        });

        // Public-site configuration — Super Admin only.
        Route::middleware('role:Super Admin')->group(function () {
            Route::get('site', [SiteSettingController::class, 'edit'])->name('site-settings.edit');
            Route::put('site', [SiteSettingController::class, 'update'])->name('site-settings.update');
        });

        // Internal users
        Route::resource('users', UserController::class)
            ->except(['show'])
            ->middleware('permission:agents.manage');

        // Agents
        Route::resource('agents', AgentController::class)
            ->middleware('permission:agents.manage');

        // Reports & export
        Route::middleware('permission:reports.view')->group(function () {
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
            Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        });

        // Configuration: categories / priorities / statuses
        Route::prefix('ticket-settings')->name('ticket-settings.')
            ->middleware('permission:settings.manage')
            ->group(function () {
                Route::get('categories', [TicketCategoryController::class, 'index'])->name('categories.index');
                Route::post('categories', [TicketCategoryController::class, 'store'])->name('categories.store');
                Route::put('categories/{category}', [TicketCategoryController::class, 'update'])->name('categories.update');
                Route::delete('categories/{category}', [TicketCategoryController::class, 'destroy'])->name('categories.destroy');

                Route::get('priorities', [TicketPriorityController::class, 'index'])->name('priorities.index');
                Route::post('priorities', [TicketPriorityController::class, 'store'])->name('priorities.store');
                Route::put('priorities/{priority}', [TicketPriorityController::class, 'update'])->name('priorities.update');
                Route::delete('priorities/{priority}', [TicketPriorityController::class, 'destroy'])->name('priorities.destroy');

                Route::get('statuses', [TicketStatusController::class, 'index'])->name('statuses.index');
                Route::post('statuses', [TicketStatusController::class, 'store'])->name('statuses.store');
                Route::put('statuses/{status}', [TicketStatusController::class, 'update'])->name('statuses.update');
                Route::delete('statuses/{status}', [TicketStatusController::class, 'destroy'])->name('statuses.destroy');

                Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
                Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
                Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
                Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
            });
    });

// ---------------------------------------------------------------------------
// Customer portal — Cliente role only
// ---------------------------------------------------------------------------
Route::prefix('portal')->name('portal.')
    ->middleware(['auth', 'role:Cliente'])
    ->group(function () {
        Route::get('/', [PortalTicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/create', [PortalTicketController::class, 'create'])->name('tickets.create');
        Route::post('tickets', [PortalTicketController::class, 'store'])->name('tickets.store');
        Route::get('tickets/{ticket}', [PortalTicketController::class, 'show'])->name('tickets.show');
        Route::get('tickets/{ticket}/messages', [PortalTicketController::class, 'messages'])->name('tickets.messages.index');
        Route::post('tickets/{ticket}/messages', [PortalTicketController::class, 'reply'])->name('tickets.messages.store');
    });
