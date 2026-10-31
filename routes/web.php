<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventExportController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// trustbridgeb2b.com (any path below) and app.trustbridgeb2b.com share this
// exact route table and document root — MarketingController is what tells
// the two domains apart; see its docblock.
Route::get('/', [MarketingController::class, 'show'])->name('marketing.ro');
Route::get('/en', [MarketingController::class, 'show'])->defaults('locale', 'en')->name('marketing.en');
Route::get('/de', [MarketingController::class, 'show'])->defaults('locale', 'de')->name('marketing.de');

// Named 'two-factor.login' (not something of our own) because Fortify's own
// FailedTwoFactorLoginResponse redirects a wrong-code submission back to a
// route of that exact name — Fortify's own GET registration under this name
// never actually reaches the route table (its POST sibling at the same URI
// does, as Laravel\Fortify\...\TwoFactorAuthenticatedSessionController@store,
// still used below), so this claims the name itself rather than leaving it
// to crash with RouteNotFoundException on the first wrong code.
Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])
    ->middleware('guest')
    ->name('two-factor.login');

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
    Route::patch('/invitatie/{participant}', [ParticipantController::class, 'requestDeletion'])->name('participants.request-deletion');

    // One link/QR code per EVENT (not per participant) — anyone who has it
    // can view published photos and ask for one to be taken down. Both
    // verbs share the same signed address, same reasoning as above.
    Route::get('/evenimente/{event}/galerie', [GalleryController::class, 'publicGallery'])->name('gallery.public');
    Route::post('/evenimente/{event}/galerie', [GalleryController::class, 'requestRemoval'])->name('gallery.public.request-removal');

    // The "export" end-of-cycle package (ProcessEventRetentionCommand) —
    // one emailed link shared by show()/confirm(), same GET/POST-same-URL
    // pattern as above; download() mints its own short-lived signature
    // fresh each time show() renders, see EventExportController.
    Route::get('/evenimente/{event}/pachet', [EventExportController::class, 'show'])->name('events.export.show');
    Route::get('/evenimente/{event}/pachet/descarca', [EventExportController::class, 'download'])->name('events.export.download');
    Route::post('/evenimente/{event}/pachet', [EventExportController::class, 'confirm'])->name('events.export.confirm');
});

Route::middleware(['app-domain', 'auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/ghid', [GuideController::class, 'index'])->name('guide.index');
    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::patch('/organizations/{organization}/status', [OrganizationController::class, 'updateStatus'])->name('organizations.update-status');
    Route::get('/organizations/{organization}/events', [OrganizationController::class, 'events'])->name('organizations.events');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::get('/events/{event}/participants', [ParticipantController::class, 'statusBoard'])->name('participants.status');
    Route::post('/events/{event}/participants', [ParticipantController::class, 'store'])->name('participants.store');
    Route::delete('/events/{event}/participants/{participant}', [ParticipantController::class, 'destroy'])->name('participants.destroy');
    Route::patch('/events/{event}/participants/{participant}/refuza-stergere', [ParticipantController::class, 'denyDeletionRequest'])->name('participants.deny-deletion');
    Route::get('/events/{event}/check-in', [ParticipantController::class, 'checkin'])->name('participants.checkin');
    Route::post('/events/{event}/check-in', [ParticipantController::class, 'checkinStore'])->name('participants.checkin.store');
    Route::get('/events/{event}/gallery', [GalleryController::class, 'index'])->name('gallery.index');
    Route::post('/events/{event}/gallery', [GalleryController::class, 'store'])->name('gallery.store');
    Route::patch('/events/{event}/gallery/{media}/publish', [GalleryController::class, 'publish'])->name('gallery.publish');
    Route::delete('/events/{event}/gallery/{media}', [GalleryController::class, 'destroy'])->name('gallery.destroy');
    Route::patch('/events/{event}/gallery/{media}/aproba-eliminare', [GalleryController::class, 'approveRemoval'])->name('gallery.approve-removal');
    Route::patch('/events/{event}/gallery/{media}/refuza-eliminare', [GalleryController::class, 'denyRemoval'])->name('gallery.deny-removal');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    Route::get('/echipa', [UserController::class, 'index'])->name('users.index');
    Route::get('/echipa/adauga', [UserController::class, 'create'])->name('users.create');
    Route::post('/echipa', [UserController::class, 'store'])->name('users.store');
    Route::delete('/echipa/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

Route::middleware(['app-domain', 'auth'])->group(function () {
    Route::patch('/locale', [LocaleController::class, 'update'])->name('locale.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Login/register/password-reset/email-verification: also app-domain-only —
// otherwise a session started on the bare marketing domain would still
// carry into every route above the moment it redirects, but any signed
// link THESE routes generate (email verification) would already be
// stamped with the wrong host, same failure as participant invitations.
Route::middleware('app-domain')->group(function () {
    require __DIR__.'/auth.php';
});
