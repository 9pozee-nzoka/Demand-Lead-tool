<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class VerifyRoutes extends Command
{
    protected $signature = 'routes:verify';
    protected $description = 'Verify all routes have working controllers and views';

    public function handle()
    {
        $this->info('🔍 Verifying Routes...');
        $this->newLine();

        $routes = collect(Route::getRoutes())->filter(function($route) {
            // Only GET routes from web middleware
            return in_array('GET', $route->methods()) 
                && in_array('web', $route->middleware())
                && !str_contains($route->uri(), '_ignition')
                && !str_contains($route->uri(), 'sanctum');
        });

        $issues = [];
        $success = 0;
        $total = $routes->count();

        foreach ($routes as $route) {
            $uri = $route->uri();
            $name = $route->getName() ?? 'unnamed';
            $action = $route->getActionName();

            // Skip closures (they're inline)
            if ($action === 'Closure') {
                $success++;
                continue;
            }

            // Check if controller exists
            if (!str_contains($action, '@')) {
                // Invokable controller or other format
                $success++;
                continue;
            }

            [$controller, $method] = explode('@', $action);
            
            if (!class_exists($controller)) {
                $issues[] = [
                    'route' => $name,
                    'uri' => $uri,
                    'issue' => "Controller not found: {$controller}",
                    'severity' => 'error'
                ];
                continue;
            }

            if (!method_exists($controller, $method)) {
                $issues[] = [
                    'route' => $name,
                    'uri' => $uri,
                    'issue' => "Method {$method} not found in {$controller}",
                    'severity' => 'error'
                ];
                continue;
            }

            $success++;
        }

        // Display results
        $this->newLine();
        $this->info("✅ Verified Routes: {$success}/{$total}");
        
        if (count($issues) > 0) {
            $this->newLine();
            $this->error("❌ Found " . count($issues) . " issues:");
            $this->newLine();
            
            foreach ($issues as $issue) {
                $this->line("  <fg=red>✗</> {$issue['route']} ({$issue['uri']})");
                $this->line("    → {$issue['issue']}");
            }
        } else {
            $this->newLine();
            $this->info('🎉 All routes are properly configured!');
        }

        $this->newLine();

        // Check key views exist
        $this->info('🔍 Verifying Key Views...');
        $this->newLine();

        $keyViews = [
            'dashboard' => 'Dashboard',
            'team.index' => 'Team Management',
            'billing.index' => 'Billing',
            'reports.index' => 'Reports',
            'integrations.index' => 'Integrations',
            'admin.dashboard' => 'Super Admin Dashboard',
            'admin.users' => 'Super Admin Users',
            'admin.settings' => 'Super Admin Settings',
            'admin.audit-log' => 'Super Admin Audit Log',
            'keywords.show' => 'Keyword Details',
            'projects.index' => 'Projects',
            'opportunities.index' => 'Opportunities',
            'leads.index' => 'Leads',
            'crm.index' => 'CRM Dashboard',
        ];

        $missingViews = [];
        foreach ($keyViews as $view => $label) {
            if (View::exists($view)) {
                $this->line("  <fg=green>✓</> {$label}");
            } else {
                $this->line("  <fg=red>✗</> {$label}");
                $missingViews[] = $view;
            }
        }

        $this->newLine();

        if (count($missingViews) > 0) {
            $this->error('Missing views: ' . implode(', ', $missingViews));
            return 1;
        }

        $this->info('✅ All key views exist!');
        $this->newLine();

        return 0;
    }
}
