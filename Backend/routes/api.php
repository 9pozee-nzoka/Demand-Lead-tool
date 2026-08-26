<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\KeywordController;
use App\Http\Controllers\Api\IntelligenceController;
use App\Http\Controllers\Api\IntegrationController;
use App\Http\Controllers\Api\ReportController;
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
    // Public — Auth (rate-limited)
    // -----------------------------------------------------------------------
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register',       [AuthController::class, 'register'])
            ->middleware('throttle:register')->name('register');
        Route::post('login',          [AuthController::class, 'login'])
            ->middleware('throttle:login')->name('login');
        Route::post('forgot-password',[AuthController::class, 'forgotPassword'])
            ->middleware('throttle:forgot-password')->name('password.forgot');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:forgot-password')->name('password.reset');
    });

    // -----------------------------------------------------------------------
    // Public — Lead Capture (no auth)
    // -----------------------------------------------------------------------
    Route::get('capture/{slug}',  [LandingPageController::class, 'captureView'])->name('capture.view');
    Route::post('capture/{slug}', [LandingPageController::class, 'capture'])->name('capture');

    // -----------------------------------------------------------------------
    // Authenticated
    // -----------------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'tenant', 'throttle:api'])->group(function () {

        // Auth
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me',      [AuthController::class, 'me'])->name('me');
        });

        // Organization — owner+ only for mutations
        Route::prefix('organization')->name('organization.')->group(function () {
            Route::get('/',  [OrganizationController::class, 'show'])->name('show');
            Route::patch('/',[OrganizationController::class, 'update'])
                ->middleware('role:owner,admin')->name('update');
        });

        // Users (team management) — admin+ only for mutations
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',         [UserController::class, 'index'])->name('index');
            Route::get('/{user}',   [UserController::class, 'show'])->name('show');
            Route::post('/',        [UserController::class, 'store'])
                ->middleware('role:owner,admin')->name('store');
            Route::patch('/{user}', [UserController::class, 'update'])
                ->middleware('role:owner,admin')->name('update');
            Route::delete('/{user}',[UserController::class, 'destroy'])
                ->middleware('role:owner')->name('destroy');
        });

        // Projects
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/',          [ProjectController::class, 'index'])->name('index');
            Route::post('/',         [ProjectController::class, 'store'])
                ->middleware('limit:projects')->name('store');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::patch('/{project}',[ProjectController::class, 'update'])->name('update');
            Route::delete('/{project}',[ProjectController::class, 'destroy'])->name('destroy');
        });

        // Keywords
        Route::prefix('keywords')->name('keywords.')->group(function () {
            Route::get('/',           [KeywordController::class, 'index'])->name('index');
            Route::post('/',          [KeywordController::class, 'store'])
                ->middleware('limit:keywords')->name('store');
            Route::post('/bulk',      [KeywordController::class, 'bulkStore'])
                ->middleware('limit:keywords')->name('bulk');
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

        // Alert Rules — admin+ only for mutations
        Route::prefix('alert-rules')->name('alert-rules.')->group(function () {
            Route::get('/',              [AlertController::class, 'indexRules'])->name('index');
            Route::post('/',             [AlertController::class, 'storeRule'])
                ->middleware('role:owner,admin,analyst')->name('store');
            Route::patch('/{alertRule}', [AlertController::class, 'updateRule'])
                ->middleware('role:owner,admin,analyst')->name('update');
            Route::delete('/{alertRule}',[AlertController::class, 'destroyRule'])
                ->middleware('role:owner,admin')->name('destroy');
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

        // Intelligence — AI enrichment, market gaps, clustering, competitors
        Route::prefix('intelligence')->name('intelligence.')->group(function () {
            Route::get('market-gaps',     [IntelligenceController::class, 'marketGaps'])->name('market-gaps');
            Route::get('clusters',        [IntelligenceController::class, 'clusters'])->name('clusters');
            Route::post('cluster',        [IntelligenceController::class, 'recluster'])->name('recluster');
            Route::post('classify-intents',[IntelligenceController::class, 'classifyIntents'])->name('classify-intents');
            Route::get('competitors',     [IntelligenceController::class, 'competitors'])->name('competitors');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('revenue-summary',    [ReportController::class, 'revenueSummary'])->name('revenue');
            Route::get('funnel',             [ReportController::class, 'funnel'])->name('funnel');
            Route::get('keywords',           [ReportController::class, 'keywords'])->name('keywords');
            // CSV exports
            Route::get('export/leads.csv',   [ReportController::class, 'exportLeads'])->name('export.leads');
            Route::get('export/deals.csv',   [ReportController::class, 'exportDeals'])->name('export.deals');
            Route::get('export/keywords.csv',[ReportController::class, 'exportKeywords'])->name('export.keywords');
        });

        // Integrations — DataSource CRUD + connection testing
        Route::prefix('integrations')->name('integrations.')->group(function () {
            Route::get('/',                          [IntegrationController::class, 'index'])->name('index');
            Route::post('/',                         [IntegrationController::class, 'store'])->name('store');
            Route::patch('/{dataSource}',            [IntegrationController::class, 'update'])->name('update');
            Route::delete('/{dataSource}',           [IntegrationController::class, 'destroy'])->name('destroy');
            Route::post('/{dataSource}/test',        [IntegrationController::class, 'test'])->name('test');
            Route::get('/{dataSource}/stats',        [IntegrationController::class, 'stats'])->name('stats');
        });

        // Billing & Usage — Sprint 16
        Route::prefix('billing')->name('billing.')->group(function () {
            Route::get('/',      [BillingController::class, 'show'])->name('show');
            Route::get('/plans', [BillingController::class, 'plans'])->name('plans');
            Route::get('/usage', [BillingController::class, 'usage'])->name('usage');
        });

    });
});

// =============================================================================
// Super-Admin Routes  —  /api/admin/*
// Protected by: auth:sanctum + super.admin middleware
// NO tenant middleware — super-admins see across all organizations
// =============================================================================
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'super.admin'])->group(function () {

    Route::get('me',                                  [AdminController::class, 'me'])->name('me');
    Route::get('metrics',                             [AdminController::class, 'metrics'])->name('metrics');
    Route::get('recent-signups',                      [AdminController::class, 'recentSignups'])->name('recent-signups');
    Route::get('audit-log',                           [AdminController::class, 'auditLog'])->name('audit-log');

    // Organizations
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/',                               [AdminController::class, 'organizations'])->name('index');
        Route::get('/{organization}',                 [AdminController::class, 'orgDetail'])->name('show');
        Route::patch('/{organization}/status',        [AdminController::class, 'setOrgStatus'])->name('status');
        Route::post('/{organization}/impersonate',    [AdminController::class, 'impersonate'])->name('impersonate');
    });
});
