<?php

use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// trustbridgeb2b.com (any path below) and app.trustbridgeb2b.com share this
// exact route table and document root — MarketingController is what tells
// the two domains apart; see its docblock.
Route::get('/', [MarketingController::class, 'show'])->name('marketing.ro');
Route::get('/en', [MarketingController::class, 'show'])->defaults('locale', 'en')->name('marketing.en');
Route::get('/de', [MarketingController::class, 'show'])->defaults('locale', 'de')->name('marketing.de');

Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])
    ->middleware('guest')
    ->name('two-factor.challenge');

// Public: a participant reaches these from a personal link, never logged in.
Route::get('/invitatie', [ParticipantController::class, 'invitation'])->name('participants.invitation');
Route::get('/invitatie/confirmare', [ParticipantController::class, 'confirmation'])->name('participants.confirmation');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/participanti', [ParticipantController::class, 'statusBoard'])->name('participants.status');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
