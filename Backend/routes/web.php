<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\KeywordController;
use App\Http\Controllers\Web\OpportunityController;
use App\Http\Controllers\Web\LeadController;
use App\Http\Controllers\Web\TrendController;
use App\Http\Controllers\Web\MarketGapController;
use App\Http\Controllers\Web\CompetitorController;
use App\Http\Controllers\Web\AlertController;
use App\Http\Controllers\Web\LandingPageController;
use App\Http\Controllers\Web\WhatsAppController;
use App\Http\Controllers\Web\DealController;
use App\Http\Controllers\Web\TaskController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        // Super admins go to admin dashboard
        if (auth()->user()->is_super_admin) {
            return redirect()->route('admin.dashboard');
        }
        // Regular users go to tenant dashboard
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

// Public Landing Pages (no auth required)
Route::get('/lp/{slug}', [LandingPageController::class, 'show'])->name('landing-pages.public');
Route::post('/lp/{landingPage}/capture', [LandingPageController::class, 'capture'])->name('landing-pages.capture');

// WhatsApp Webhook (no auth required)
Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'handle'])->name('whatsapp.webhook');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects
    Route::resource('projects', ProjectController::class);

    // Keywords
    Route::resource('keywords', KeywordController::class)->except(['edit', 'update']);

    // Trends
    Route::get('/trends', [TrendController::class, 'index'])->name('trends.index');

    // Opportunities
    Route::resource('opportunities', OpportunityController::class)->only(['index', 'show']);
    Route::post('/opportunities/{opportunity}/dismiss', [OpportunityController::class, 'dismiss'])->name('opportunities.dismiss');
    Route::post('/opportunities/{opportunity}/restore', [OpportunityController::class, 'restore'])->name('opportunities.restore');

    // Market Gaps
    Route::get('/market-gaps', [MarketGapController::class, 'index'])->name('market-gaps.index');
    Route::post('/market-gaps/refresh', [MarketGapController::class, 'refresh'])->name('market-gaps.refresh');

    // Competitors
    Route::get('/competitors', [CompetitorController::class, 'index'])->name('competitors.index');
    Route::post('/competitors/analyze', [CompetitorController::class, 'analyze'])->name('competitors.analyze');

    // Leads
    Route::resource('leads', LeadController::class);
    Route::post('/leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');

    // Landing Pages
    Route::get('/landing-pages', [LandingPageController::class, 'index'])->name('landing-pages.index');
    Route::get('/landing-pages/create', [LandingPageController::class, 'create'])->name('landing-pages.create');
    Route::post('/landing-pages/generate', [LandingPageController::class, 'generate'])->name('landing-pages.generate');
    Route::get('/landing-pages/{landingPage}/edit', [LandingPageController::class, 'edit'])->name('landing-pages.edit');
    Route::patch('/landing-pages/{landingPage}', [LandingPageController::class, 'update'])->name('landing-pages.update');
    Route::delete('/landing-pages/{landingPage}', [LandingPageController::class, 'destroy'])->name('landing-pages.destroy');
    Route::get('/landing-pages/{landingPage}/preview', [LandingPageController::class, 'preview'])->name('landing-pages.preview');
    Route::get('/landing-pages/{landingPage}/analytics', [LandingPageController::class, 'analytics'])->name('landing-pages.analytics');
    Route::patch('/landing-pages/{landingPage}/publish', [LandingPageController::class, 'publish'])->name('landing-pages.publish');
    Route::patch('/landing-pages/{landingPage}/unpublish', [LandingPageController::class, 'unpublish'])->name('landing-pages.unpublish');
    Route::post('/landing-pages/{landingPage}/duplicate', [LandingPageController::class, 'duplicate'])->name('landing-pages.duplicate');
    Route::post('/landing-pages/{landingPage}/regenerate', [LandingPageController::class, 'regenerateSection'])->name('landing-pages.regenerate');

    // CRM
    Route::get('/crm', [DealController::class, 'index'])->name('crm.deals');
    
    // Deals
    Route::resource('deals', DealController::class);
    Route::post('/deals/{deal}/update-stage', [DealController::class, 'updateStage'])->name('deals.update-stage');
    Route::patch('/deals/{deal}/mark-won', [DealController::class, 'markAsWon'])->name('deals.mark-won');
    Route::patch('/deals/{deal}/mark-lost', [DealController::class, 'markAsLost'])->name('deals.mark-lost');
    Route::get('/deals-analytics', [DealController::class, 'analytics'])->name('deals.analytics');

    // Tasks
    Route::resource('tasks', TaskController::class);
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::post('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');
    Route::get('/my-tasks', [TaskController::class, 'myTasks'])->name('tasks.my-tasks');
    Route::get('/tasks-calendar', [TaskController::class, 'calendar'])->name('tasks.calendar');
    Route::get('/tasks-timeline', [TaskController::class, 'timeline'])->name('tasks.timeline');

    // Reports
    Route::get('/reports', function() { return view('reports.index'); })->name('reports.index');

    // Alerts
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/rules', [AlertController::class, 'rules'])->name('alerts.rules');
    Route::get('/alerts/rules/create', [AlertController::class, 'createRule'])->name('alerts.rules.create');
    Route::post('/alerts/rules', [AlertController::class, 'storeRule'])->name('alerts.rules.store');
    Route::get('/alerts/rules/{alertRule}/edit', [AlertController::class, 'editRule'])->name('alerts.rules.edit');
    Route::patch('/alerts/rules/{alertRule}', [AlertController::class, 'updateRule'])->name('alerts.rules.update');
    Route::delete('/alerts/rules/{alertRule}', [AlertController::class, 'destroyRule'])->name('alerts.rules.destroy');
    Route::post('/alerts/rules/{alertRule}/toggle', [AlertController::class, 'toggleRule'])->name('alerts.rules.toggle');
    Route::post('/alerts/{alert}/read', [AlertController::class, 'markAsRead'])->name('alerts.read');
    Route::post('/alerts/read-all', [AlertController::class, 'markAllAsRead'])->name('alerts.read-all');

    // Integrations
    Route::get('/integrations', function() { return view('integrations.index'); })->name('integrations.index');

    // WhatsApp Integration
    Route::prefix('integrations/whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/', [WhatsAppController::class, 'index'])->name('index');
        Route::get('/configure', [WhatsAppController::class, 'configure'])->name('configure');
        Route::post('/configure', [WhatsAppController::class, 'store'])->name('store');
        Route::post('/test', [WhatsAppController::class, 'test'])->name('test');
        Route::post('/send-test', [WhatsAppController::class, 'sendTest'])->name('send-test');
        Route::post('/disconnect', [WhatsAppController::class, 'disconnect'])->name('disconnect');
        Route::get('/statistics', [WhatsAppController::class, 'statistics'])->name('statistics');
        Route::get('/conversation/{lead}', [WhatsAppController::class, 'conversation'])->name('conversation');
        Route::post('/conversation/{lead}/send', [WhatsAppController::class, 'sendMessage'])->name('send-message');
    });

    // Team Management
    Route::get('/team', [\App\Http\Controllers\Web\TeamController::class, 'index'])->name('team.index');
    Route::post('/team/invite', [\App\Http\Controllers\Web\TeamController::class, 'invite'])->name('team.invite')->middleware('role:admin,owner');
    Route::patch('/team/{user}/status', [\App\Http\Controllers\Web\TeamController::class, 'updateStatus'])->name('team.update-status')->middleware('role:admin,owner');
    Route::delete('/team/{user}', [\App\Http\Controllers\Web\TeamController::class, 'destroy'])->name('team.destroy')->middleware('role:admin,owner');

    // Reports & Analytics
    Route::get('/reports', [\App\Http\Controllers\Web\ReportsController::class, 'index'])->name('reports.index');

    // Billing & Subscription
    Route::get('/billing', [\App\Http\Controllers\Web\BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/change-plan', [\App\Http\Controllers\Web\BillingController::class, 'changePlan'])->name('billing.change-plan')->middleware('role:owner,admin');
    Route::post('/billing/cancel', [\App\Http\Controllers\Web\BillingController::class, 'cancelSubscription'])->name('billing.cancel')->middleware('role:owner');

    // Data Sources Management
    Route::prefix('sources')->name('sources.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\SourcesController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Web\SourcesController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Web\SourcesController::class, 'store'])->name('store');
        Route::get('/{source}', [\App\Http\Controllers\Web\SourcesController::class, 'show'])->name('show');
        Route::get('/{source}/edit', [\App\Http\Controllers\Web\SourcesController::class, 'edit'])->name('edit');
        Route::patch('/{source}', [\App\Http\Controllers\Web\SourcesController::class, 'update'])->name('update');
        Route::delete('/{source}', [\App\Http\Controllers\Web\SourcesController::class, 'destroy'])->name('destroy');
        Route::post('/{source}/test', [\App\Http\Controllers\Web\SourcesController::class, 'test'])->name('test');
        Route::post('/{source}/run', [\App\Http\Controllers\Web\SourcesController::class, 'run'])->name('run');
        Route::post('/{source}/pause', [\App\Http\Controllers\Web\SourcesController::class, 'pause'])->name('pause');
        Route::post('/{source}/activate', [\App\Http\Controllers\Web\SourcesController::class, 'activate'])->name('activate');
        Route::get('/{source}/items', [\App\Http\Controllers\Web\SourcesController::class, 'items'])->name('items');
        Route::get('/{source}/events', [\App\Http\Controllers\Web\SourcesController::class, 'events'])->name('events');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Super Admin Panel
Route::prefix('super-admin')->middleware(['auth', App\Http\Middleware\EnsureSuperAdmin::class])->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Web\AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/organizations', [\App\Http\Controllers\Web\AdminDashboardController::class, 'organizations'])->name('organizations');
    Route::get('/organizations/{organization}', [\App\Http\Controllers\Web\AdminDashboardController::class, 'organizationDetail'])->name('organizations.show');
    Route::get('/users', [\App\Http\Controllers\Web\AdminDashboardController::class, 'users'])->name('users');
    Route::get('/audit-log', [\App\Http\Controllers\Web\AdminDashboardController::class, 'auditLog'])->name('audit-log');
    Route::get('/settings', [\App\Http\Controllers\Web\AdminDashboardController::class, 'settings'])->name('settings');
});

// Two-Factor Authentication
Route::prefix('security/2fa')->middleware('auth')->name('2fa.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\TwoFactorAuthController::class, 'index'])->name('index');
    Route::get('/enable', [\App\Http\Controllers\Web\TwoFactorAuthController::class, 'enable'])->name('enable');
    Route::post('/confirm', [\App\Http\Controllers\Web\TwoFactorAuthController::class, 'confirm'])->name('confirm');
    Route::get('/recovery-codes', [\App\Http\Controllers\Web\TwoFactorAuthController::class, 'showRecoveryCodes'])->name('recovery-codes');
    Route::post('/verify-password', [\App\Http\Controllers\Web\TwoFactorAuthController::class, 'verifyPassword'])->name('verify-password');
    Route::post('/regenerate-codes', [\App\Http\Controllers\Web\TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->name('regenerate-codes');
    Route::delete('/disable', [\App\Http\Controllers\Web\TwoFactorAuthController::class, 'disable'])->name('disable');
});

// 2FA Verification (during login - no auth required)
Route::get('/2fa/verify', function () {
    if (!session()->has('2fa_user_id')) {
        return redirect()->route('login');
    }
    return view('security.two-factor.verify');
})->name('2fa.verify.show');
Route::post('/2fa/verify', [\App\Http\Controllers\Web\TwoFactorAuthController::class, 'verify'])->name('2fa.verify');

// Email Campaigns
Route::middleware('auth')->prefix('campaigns')->name('campaigns.')->group(function () {
    // Campaign Management
    Route::get('/', [\App\Http\Controllers\Web\EmailCampaignController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Web\EmailCampaignController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Web\EmailCampaignController::class, 'store'])->name('store');
    Route::get('/{campaign}', [\App\Http\Controllers\Web\EmailCampaignController::class, 'show'])->name('show');
    Route::get('/{campaign}/edit', [\App\Http\Controllers\Web\EmailCampaignController::class, 'edit'])->name('edit');
    Route::patch('/{campaign}', [\App\Http\Controllers\Web\EmailCampaignController::class, 'update'])->name('update');
    Route::delete('/{campaign}', [\App\Http\Controllers\Web\EmailCampaignController::class, 'destroy'])->name('destroy');
    
    // Campaign Actions
    Route::post('/{campaign}/send', [\App\Http\Controllers\Web\EmailCampaignController::class, 'send'])->name('send');
    Route::post('/{campaign}/pause', [\App\Http\Controllers\Web\EmailCampaignController::class, 'pause'])->name('pause');
    Route::post('/{campaign}/cancel', [\App\Http\Controllers\Web\EmailCampaignController::class, 'cancel'])->name('cancel');
    Route::post('/{campaign}/duplicate', [\App\Http\Controllers\Web\EmailCampaignController::class, 'duplicate'])->name('duplicate');
    
    // Email Templates
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\EmailTemplateController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Web\EmailTemplateController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Web\EmailTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [\App\Http\Controllers\Web\EmailTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [\App\Http\Controllers\Web\EmailTemplateController::class, 'edit'])->name('edit');
        Route::patch('/{template}', [\App\Http\Controllers\Web\EmailTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [\App\Http\Controllers\Web\EmailTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{template}/duplicate', [\App\Http\Controllers\Web\EmailTemplateController::class, 'duplicate'])->name('duplicate');
        Route::get('/{template}/preview', [\App\Http\Controllers\Web\EmailTemplateController::class, 'preview'])->name('preview');
    });
});

// Analytics Dashboard
Route::middleware('auth')->prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\AnalyticsController::class, 'index'])->name('index');
    Route::get('/export-csv', [\App\Http\Controllers\Web\AnalyticsController::class, 'exportCsv'])->name('export-csv');
    Route::get('/export-detailed', [\App\Http\Controllers\Web\AnalyticsController::class, 'exportDetailedCsv'])->name('export-detailed');
});

// ── Super Admin Panel ─────────────────────────────────────────────────────────
Route::prefix('super-admin')
    ->middleware(['auth', App\Http\Middleware\EnsureSuperAdmin::class])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Web\AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/organizations', [\App\Http\Controllers\Web\AdminDashboardController::class, 'organizations'])->name('organizations');
        Route::get('/organizations/{organization}', [\App\Http\Controllers\Web\AdminDashboardController::class, 'organizationDetail'])->name('organizations.show');
        Route::get('/users', [\App\Http\Controllers\Web\AdminDashboardController::class, 'users'])->name('users');
        Route::get('/audit-log', [\App\Http\Controllers\Web\AdminDashboardController::class, 'auditLog'])->name('audit-log');
        Route::get('/settings', [\App\Http\Controllers\Web\AdminDashboardController::class, 'settings'])->name('settings');
        
        // User Impersonation
        Route::post('/impersonate/{user}', [\App\Http\Controllers\Admin\ImpersonateController::class, 'start'])->name('impersonate');
    });

// Stop Impersonation (available when impersonating)
Route::post('/stop-impersonating', [\App\Http\Controllers\Admin\ImpersonateController::class, 'stop'])
    ->middleware('auth')
    ->name('impersonate.stop');

