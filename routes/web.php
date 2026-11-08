<?php

declare(strict_types=1);

use App\Http\Controllers\AnalysisNoteCommentController;
use App\Http\Controllers\AnalysisNoteEvidenceController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PollVoteController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\SubmissionDuplicateCheckController;
use App\Http\Controllers\UrlSubmissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserNoteCommentController;
use App\Http\Controllers\UserNoteController;
use App\Http\Controllers\UserPasskeyController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    // Submissions...
    Route::get('dashboard', [SubmissionController::class, 'create'])->name('dashboard');
    Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::post('submissions', [SubmissionController::class, 'store'])->name('submissions.store');
    Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');

    // URL Submissions...
    Route::post('url-submissions', [UrlSubmissionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('url-submissions.store');

    // Submission Duplicate Checks...
    Route::post('submission-duplicate-checks', [SubmissionDuplicateCheckController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('submission-duplicate-checks.store');

    // Community Notes...
    Route::post('submissions/{submission}/user-notes', [UserNoteController::class, 'store'])->name('submissions.user-notes.store');

    // Comments...
    Route::post('analysis-notes/{analysisNote}/comments', [AnalysisNoteCommentController::class, 'store'])->name('analysis-notes.comments.store');
    Route::post('user-notes/{userNote}/comments', [UserNoteCommentController::class, 'store'])->name('user-notes.comments.store');

    // Evidence...
    Route::post('analysis-notes/{analysisNote}/evidence', [AnalysisNoteEvidenceController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('analysis-notes.evidence.store');

    // Questions...
    Route::get('questions', [QuestionController::class, 'index'])->name('questions.index');
    Route::get('questions/{question}', [QuestionController::class, 'show'])->name('questions.show');

    // Public Questions...
    Route::get('polls', [PollController::class, 'index'])->name('polls.index');
    Route::post('polls/{poll}/votes', [PollVoteController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('polls.votes.store');

    // Reports...
    Route::post('reports', [ReportController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('reports.store');

    // Invitations...
    Route::get('invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('invitations', [InvitationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('invitations.store');
});

Route::middleware('auth')->group(function (): void {
    // User Profile...
    Route::get('settings', fn (): RedirectResponse => to_route('user-profile.edit'));
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    // User...
    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

    // User Password...
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/Update'))->name('appearance.edit');

    // User Two-Factor Authentication...
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // User Passkeys...
    Route::get('settings/passkeys', [UserPasskeyController::class, 'index'])
        ->name('user-passkey.index');
});

Route::middleware('guest')->group(function (): void {
    // User...
    Route::get('register', [UserController::class, 'create'])
        ->name('register');
    Route::post('register', [UserController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('register.store');

    // User Password...
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->name('password.email');

    // Session...
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});

// Passkey Endpoints...
Route::get('.well-known/passkey-endpoints', fn () => response()->json([
    'enroll' => route('user-passkey.index'),
    'manage' => route('user-passkey.index'),
]))->name('well-known.passkeys');
