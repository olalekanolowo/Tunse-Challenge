<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\ClaimTypeController;
use App\Http\Controllers\Api\CommunitySubmissionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DisqualificationController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\InstitutionController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\LeaderboardSnapshotController;
use App\Http\Controllers\Api\PhaseController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\ScoreAdjustmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Route groups are added incrementally as each domain area is built out.
| See the implementation plan for the full intended surface.
|
*/

Route::middleware('throttle:api')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/institutions', [InstitutionController::class, 'index']);
    Route::get('/phases', [PhaseController::class, 'index']);
    Route::get('/phases/{phase}/claim-types', [ClaimTypeController::class, 'forPhase']);
    Route::get('/states', [ReferenceController::class, 'states']);
    Route::get('/categories', [ReferenceController::class, 'categories']);
    Route::get('/badges', [BadgeController::class, 'index']);

    Route::get('/auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/email/resend', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:verification-resend');

        Route::middleware('verified')->group(function () {
            Route::middleware('role:admin,super_admin')->group(function () {
                Route::post('/admin/institutions', [InstitutionController::class, 'store']);
                Route::patch('/admin/institutions/{institution}', [InstitutionController::class, 'update']);
                Route::post('/admin/phases', [PhaseController::class, 'store']);
                Route::patch('/admin/phases/{phase}', [PhaseController::class, 'update']);
                Route::post('/admin/phases/{phase}/transition', [PhaseController::class, 'transition']);
                Route::post('/admin/claim-types', [ClaimTypeController::class, 'store']);
                Route::patch('/admin/claim-types/{claimType}', [ClaimTypeController::class, 'update']);
                Route::post('/admin/disqualifications', [DisqualificationController::class, 'store']);
                Route::post('/admin/disqualifications/{disqualification}/reinstate', [DisqualificationController::class, 'reinstate']);
                Route::post('/admin/score-adjustments', [ScoreAdjustmentController::class, 'store']);
                Route::post('/admin/badges', [BadgeController::class, 'store']);
                Route::patch('/admin/badges/{badge}', [BadgeController::class, 'update']);
                Route::post('/admin/user-badges', [BadgeController::class, 'award']);
                Route::post('/admin/leaderboard-snapshots', [LeaderboardSnapshotController::class, 'store']);
            });

            Route::middleware('role:admin,auditor,super_admin')->group(function () {
                Route::get('/admin/institutions', [InstitutionController::class, 'index']);
                Route::get('/admin/disqualifications', [DisqualificationController::class, 'index']);
                Route::get('/admin/score-adjustments', [ScoreAdjustmentController::class, 'index']);
                Route::get('/admin/dashboard', [DashboardController::class, 'index']);
                Route::get('/admin/leaderboard-snapshots', [LeaderboardSnapshotController::class, 'index']);
                Route::get('/admin/exports/claims.csv', [ExportController::class, 'claims']);
                Route::get('/admin/exports/students.csv', [ExportController::class, 'students']);
                Route::get('/admin/exports/institutions.csv', [ExportController::class, 'institutions']);
                Route::get('/admin/exports/audit-log.csv', [ExportController::class, 'auditLog']);
                Route::get('/admin/exports/leaderboard/institution.csv', [ExportController::class, 'institutionLeaderboard']);
                Route::get('/admin/exports/leaderboard/individual.csv', [ExportController::class, 'individualLeaderboard']);
            });

            Route::get('/claims/{claim}', [ClaimController::class, 'show']);
            Route::get('/claims/{claim}/photo', [FileController::class, 'claimPhoto']);

            Route::middleware('role:student')->group(function () {
                Route::get('/claims', [ClaimController::class, 'index']);
                Route::post('/claims', [ClaimController::class, 'store'])->middleware('throttle:claims');
                Route::patch('/claims/{claim}', [ClaimController::class, 'update'])->middleware('throttle:claims');
                Route::get('/community-submissions', [CommunitySubmissionController::class, 'index']);
                Route::post('/community-submissions', [CommunitySubmissionController::class, 'store'])->middleware('throttle:claims');
                Route::get('/me/badges', [BadgeController::class, 'myBadges']);
            });

            Route::middleware('role:admin,auditor,super_admin')->group(function () {
                Route::get('/students/{studentProfile}/id-file', [FileController::class, 'studentIdFile']);
                Route::get('/admin/community-submissions', [CommunitySubmissionController::class, 'index']);
                Route::patch('/admin/community-submissions/{communitySubmission}/score-note', [CommunitySubmissionController::class, 'updateScoreNote']);
                Route::get('/admin/claims', [ClaimController::class, 'adminIndex']);
                Route::post('/admin/claims/{claim}/review', [ClaimController::class, 'review']);
            });

            Route::get('/leaderboards/individual', [LeaderboardController::class, 'individual']);
            Route::get('/leaderboards/institution', [LeaderboardController::class, 'institution']);
            Route::get('/leaderboards/cumulative', [LeaderboardController::class, 'cumulative']);
        });
    });
});
