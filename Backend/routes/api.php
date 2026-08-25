<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\KeywordController;
use App\Http\Controllers\Api\LandingPageController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TrendController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| All routes are prefixed with /api/v1 via the RouteServiceProvider.
| Protected routes use Sanctum token authentication.
| Tenant isolation is enforced by the 'tenant' middleware.
*/

Route::prefix('v1')->group(function () {

    // -----------------------------------------------------------------------
    // Public — Auth
    // -----------------------------------------------------------------------
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register',       [AuthController::class, 'register'])->name('register');
        Route::post('login',          [AuthController::class, 'login'])->name('login');
        Route::post('forgot-password',[AuthController::class, 'forgotPassword'])->name('password.forgot');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
    });

    // -----------------------------------------------------------------------
    // Public — Lead Capture (no auth)
    // -----------------------------------------------------------------------
    Route::post('v1/capture/{slug}', [LandingPageController::class, 'capture'])->name('capture');

    // -----------------------------------------------------------------------
    // Authenticated
    // -----------------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

        // Auth
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me',      [AuthController::class, 'me'])->name('me');
        });

        // Organization
        Route::prefix('organization')->name('organization.')->group(function () {
            Route::get('/',  [OrganizationController::class, 'show'])->name('show');
            Route::patch('/',[OrganizationController::class, 'update'])->name('update');
        });

        // Users (team management)
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',         [UserController::class, 'index'])->name('index');
            Route::post('/',        [UserController::class, 'store'])->name('store');
            Route::get('/{user}',   [UserController::class, 'show'])->name('show');
            Route::patch('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}',[UserController::class, 'destroy'])->name('destroy');
        });

        // Projects
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/',          [ProjectController::class, 'index'])->name('index');
            Route::post('/',         [ProjectController::class, 'store'])->name('store');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::patch('/{project}',[ProjectController::class, 'update'])->name('update');
            Route::delete('/{project}',[ProjectController::class, 'destroy'])->name('destroy');
        });

        // Keywords
        Route::prefix('keywords')->name('keywords.')->group(function () {
            Route::get('/',           [KeywordController::class, 'index'])->name('index');
            Route::post('/',          [KeywordController::class, 'store'])->name('store');
            Route::post('/bulk',      [KeywordController::class, 'bulkStore'])->name('bulk');
            Route::get('/{keyword}',  [KeywordController::class, 'show'])->name('show');
            Route::patch('/{keyword}',[KeywordController::class, 'update'])->name('update');
            Route::delete('/{keyword}',[KeywordController::class, 'destroy'])->name('destroy');
            Route::get('/{keyword}/trend',  [KeywordController::class, 'trend'])->name('trend');
            Route::get('/{keyword}/history',[KeywordController::class, 'history'])->name('history');
        });

        // Trends
        Route::prefix('trends')->name('trends.')->group(function () {
            Route::get('/',        [TrendController::class, 'index'])->name('index');
            Route::get('/rising',  [TrendController::class, 'rising'])->name('rising');
        });

        // Opportunities
        Route::prefix('opportunities')->name('opportunities.')->group(function () {
            Route::get('/',                          [OpportunityController::class, 'index'])->name('index');
            Route::get('/{opportunity}',             [OpportunityController::class, 'show'])->name('show');
            Route::patch('/{opportunity}',           [OpportunityController::class, 'update'])->name('update');
            Route::post('/{opportunity}/action',     [OpportunityController::class, 'action'])->name('action');
            Route::post('/{opportunity}/dismiss',    [OpportunityController::class, 'dismiss'])->name('dismiss');
        });

        // Leads
        Route::prefix('leads')->name('leads.')->group(function () {
            Route::get('/',             [LeadController::class, 'index'])->name('index');
            Route::post('/',            [LeadController::class, 'store'])->name('store');
            Route::get('/{lead}',       [LeadController::class, 'show'])->name('show');
            Route::patch('/{lead}',     [LeadController::class, 'update'])->name('update');
            Route::post('/{lead}/qualify', [LeadController::class, 'qualify'])->name('qualify');
            Route::post('/{lead}/assign',  [LeadController::class, 'assign'])->name('assign');
            Route::post('/{lead}/convert', [LeadController::class, 'convert'])->name('convert');
        });

        // CRM
        Route::prefix('deals')->name('deals.')->group(function () {
            Route::get('/',          [DealController::class, 'index'])->name('index');
            Route::post('/',         [DealController::class, 'store'])->name('store');
            Route::get('/{deal}',    [DealController::class, 'show'])->name('show');
            Route::patch('/{deal}',  [DealController::class, 'update'])->name('update');
            Route::delete('/{deal}', [DealController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('/',          [TaskController::class, 'index'])->name('index');
            Route::post('/',         [TaskController::class, 'store'])->name('store');
            Route::patch('/{task}',  [TaskController::class, 'update'])->name('update');
            Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('notes')->name('notes.')->group(function () {
            Route::get('/',          [NoteController::class, 'index'])->name('index');
            Route::post('/',         [NoteController::class, 'store'])->name('store');
            Route::patch('/{note}',  [NoteController::class, 'update'])->name('update');
            Route::delete('/{note}', [NoteController::class, 'destroy'])->name('destroy');
        });

        // Alerts
        Route::prefix('alerts')->name('alerts.')->group(function () {
            Route::get('/',                        [AlertController::class, 'index'])->name('index');
            Route::patch('/{alert}/read',          [AlertController::class, 'markRead'])->name('read');
            Route::post('/read-all',               [AlertController::class, 'markAllRead'])->name('read-all');
        });

        Route::prefix('alert-rules')->name('alert-rules.')->group(function () {
            Route::get('/',              [AlertController::class, 'indexRules'])->name('index');
            Route::post('/',             [AlertController::class, 'storeRule'])->name('store');
            Route::patch('/{alertRule}', [AlertController::class, 'updateRule'])->name('update');
            Route::delete('/{alertRule}',[AlertController::class, 'destroyRule'])->name('destroy');
        });

        // Landing Pages
        Route::prefix('landing-pages')->name('landing-pages.')->group(function () {
            Route::get('/',                   [LandingPageController::class, 'index'])->name('index');
            Route::post('/',                  [LandingPageController::class, 'store'])->name('store');
            Route::get('/{landingPage}',      [LandingPageController::class, 'show'])->name('show');
            Route::patch('/{landingPage}',    [LandingPageController::class, 'update'])->name('update');
            Route::delete('/{landingPage}',   [LandingPageController::class, 'destroy'])->name('destroy');
        });

        // Dashboard
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/',      [DashboardController::class, 'index'])->name('index');
            Route::get('/funnel',[DashboardController::class, 'funnel'])->name('funnel');
            Route::get('/roi',   [DashboardController::class, 'roi'])->name('roi');
        });

        // Billing / Usage (stubs — full implementation Sprint 11+)
        Route::get('billing', fn () => response()->json(['message' => 'Coming soon.']))->name('billing');
        Route::get('usage',   fn () => response()->json(['message' => 'Coming soon.']))->name('usage');

        // Integrations (stubs)
        Route::get('integrations', fn () => response()->json(['message' => 'Coming soon.']))->name('integrations.index');
        Route::post('integrations/{provider}', fn () => response()->json(['message' => 'Coming soon.']))->name('integrations.connect');
    });
});
