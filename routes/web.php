<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
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

// Public, static walkthrough with one sample event — what the marketing
// page's "Vezi invitația demo" actually links to. Deliberately untouched by
// AP10-13 below: no participant row, no signature, nothing to confuse with
// a real recipient's data.
Route::get('/invitatie', [ParticipantController::class, 'invitation'])->name('participants.invitation');
Route::get('/invitatie/confirmare', [ParticipantController::class, 'confirmation'])->name('participants.confirmation');

// Real per-recipient flow (AP10-13). A participant reaches these from their
// personal signed link (A-03), never logged in. Both verbs share one
// URL/signature — the GET shows the form (or the confirmation, once
// responded_at is set) and the POST submits to that exact same signed
// address.
Route::middleware('signed')->group(function () {
    Route::get('/invitatie/{participant}', [ParticipantController::class, 'show'])->name('participants.show');
    Route::post('/invitatie/{participant}', [ParticipantController::class, 'respond'])->name('participants.respond');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/participants', [ParticipantController::class, 'statusBoard'])->name('participants.status');
    Route::post('/events/{event}/participants', [ParticipantController::class, 'store'])->name('participants.store');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
