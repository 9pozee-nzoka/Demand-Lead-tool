<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed comprehensive demo data for every feature.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting DemoDataSeeder...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->truncateTables();

        $this->command->info('  ✓ Seeding plans...');
        $plans = $this->seedPlans();

        $this->command->info('  ✓ Seeding organizations & subscriptions...');
        [$orgA, $orgB] = $this->seedOrganizations($plans);

        $this->command->info('  ✓ Seeding users...');
        $usersA = $this->seedUsers($orgA);
        $usersB = $this->seedUsers($orgB, 'b');

        $this->command->info('  ✓ Seeding projects & keywords...');
        $projectsA = $this->seedProjects($orgA, $usersA);

        $this->command->info('  ✓ Seeding keyword measurements...');
        $this->seedKeywordMeasurements($projectsA);

        $this->command->info('  ✓ Seeding demand clusters...');
        $clusters = $this->seedDemandClusters($projectsA);

        $this->command->info('  ✓ Seeding opportunities...');
        $opportunities = $this->seedOpportunities($orgA, $projectsA, $clusters);

        $this->command->info('  ✓ Seeding landing pages...');
        $landingPages = $this->seedLandingPages($orgA, $projectsA, $opportunities);

        $this->command->info('  ✓ Seeding leads & contacts...');
        [$leads, $contacts] = $this->seedLeadsAndContacts($orgA, $projectsA, $opportunities, $landingPages, $usersA);

        $this->command->info('  ✓ Seeding CRM (deals, tasks, notes)...');
        $deals = $this->seedDeals($orgA, $leads, $contacts, $usersA);
        $this->seedTasks($orgA, $deals, $leads, $usersA);
        $this->seedNotes($orgA, $deals, $leads, $usersA);

        $this->command->info('  ✓ Seeding alert rules & alerts...');
        $this->seedAlerts($orgA, $projectsA, $opportunities, $leads);

        $this->command->info('  ✓ Seeding email templates & campaigns...');
        $this->seedEmailCampaigns($orgA, $opportunities, $leads, $usersA);

        $this->command->info('  ✓ Seeding audit logs...');
        $this->seedAuditLogs($orgA, $usersA);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('');
        $this->command->info('✅ DemoDataSeeder complete!');
        $this->command->info('');
        $this->command->info('  Demo login credentials:');
        $this->command->info('  ─────────────────────────────────────────────');
        $this->command->info('  Owner   : owner@demo.com      / password');
        $this->command->info('  Admin   : admin@demo.com      / password');
        $this->command->info('  Analyst : analyst@demo.com    / password');
        $this->command->info('  Sales   : sales@demo.com      / password');
        $this->command->info('  Marketing: marketing@demo.com / password');
        $this->command->info('  ─────────────────────────────────────────────');
    }

    // ─────────────────────────────────────────────────────────────
    // TRUNCATE
    // ─────────────────────────────────────────────────────────────

    private function truncateTables(): void
    {
        $tables = [
            'audit_logs', 'usage_records', 'campaign_recipients', 'email_campaigns',
            'email_templates', 'alerts', 'alert_rules', 'notes', 'tasks', 'deals',
            'contacts', 'lead_events', 'leads', 'landing_pages', 'cluster_keywords',
            'demand_clusters', 'keyword_measurements', 'keyword_locations', 'keywords',
            'opportunities', 'projects', 'subscriptions', 'personal_access_tokens',
            'users', 'organizations', 'plans',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        // Also truncate the old campaigns table if it exists
        if (DB::getSchemaBuilder()->hasTable('campaigns')) {
            DB::table('campaigns')->truncate();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // PLANS
    // ─────────────────────────────────────────────────────────────

    private function seedPlans(): array
    {
        $plans = [
            [
                'name'          => 'Starter',
                'slug'          => 'starter',
                'monthly_price' => 49.00,
                'limits'        => json_encode(['projects' => 2, 'keywords' => 50, 'leads' => 200, 'users' => 3]),
                'features'      => json_encode(['trends', 'opportunities', 'leads']),
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'Growth',
                'slug'          => 'growth',
                'monthly_price' => 149.00,
                'limits'        => json_encode(['projects' => 10, 'keywords' => 500, 'leads' => 2000, 'users' => 10]),
                'features'      => json_encode(['trends', 'opportunities', 'leads', 'crm', 'alerts', 'campaigns']),
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'name'          => 'Pro',
                'slug'          => 'pro',
                'monthly_price' => 349.00,
                'limits'        => json_encode(['projects' => -1, 'keywords' => -1, 'leads' => -1, 'users' => -1]),
                'features'      => json_encode(['trends', 'opportunities', 'leads', 'crm', 'alerts', 'campaigns', 'ai', 'api', 'webhooks']),
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        foreach ($plans as &$plan) {
            $plan['id'] = DB::table('plans')->insertGetId($plan);
        }

        return $plans;
    }

    // ─────────────────────────────────────────────────────────────
    // ORGANIZATIONS
    // ─────────────────────────────────────────────────────────────

    private function seedOrganizations(array $plans): array
    {
        $growthPlan = collect($plans)->firstWhere('slug', 'growth');
        $proPlan    = collect($plans)->firstWhere('slug', 'pro');

        $orgAId = DB::table('organizations')->insertGetId([
            'name'       => 'Acme Marketing Co.',
            'slug'       => 'acme-marketing',
            'industry'   => 'Digital Marketing',
            'country'    => 'US',
            'timezone'   => 'America/New_York',
            'plan_id'    => $growthPlan['id'],
            'status'     => 'active',
            'created_at' => now()->subMonths(6),
            'updated_at' => now(),
        ]);

        DB::table('subscriptions')->insert([
            'organization_id' => $orgAId,
            'plan_id'         => $growthPlan['id'],
            'provider'        => 'stripe',
            'external_id'     => 'sub_demo_acme_' . Str::random(8),
            'status'          => 'active',
            'renewal_at'      => now()->addMonth(),
            'created_at'      => now()->subMonths(6),
            'updated_at'      => now(),
        ]);

        $orgBId = DB::table('organizations')->insertGetId([
            'name'       => 'TechGrowth SaaS',
            'slug'       => 'techgrowth-saas',
            'industry'   => 'SaaS / Technology',
            'country'    => 'US',
            'timezone'   => 'America/Los_Angeles',
            'plan_id'    => $proPlan['id'],
            'status'     => 'active',
            'created_at' => now()->subMonths(3),
            'updated_at' => now(),
        ]);

        DB::table('subscriptions')->insert([
            'organization_id' => $orgBId,
            'plan_id'         => $proPlan['id'],
            'provider'        => 'stripe',
            'external_id'     => 'sub_demo_tech_' . Str::random(8),
            'status'          => 'active',
            'renewal_at'      => now()->addMonth(),
            'created_at'      => now()->subMonths(3),
            'updated_at'      => now(),
        ]);

        return [
            ['id' => $orgAId, 'name' => 'Acme Marketing Co.'],
            ['id' => $orgBId, 'name' => 'TechGrowth SaaS'],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // USERS
    // ─────────────────────────────────────────────────────────────

    private function seedUsers(array $org, string $suffix = 'a'): array
    {
        $users = [];

        $roles = [
            ['name' => 'Alex Owner',    'email' => "owner@demo.com",     'role' => 'owner'],
            ['name' => 'Blair Admin',   'email' => "admin@demo.com",     'role' => 'admin'],
            ['name' => 'Casey Analyst', 'email' => "analyst@demo.com",   'role' => 'analyst'],
            ['name' => 'Dana Sales',    'email' => "sales@demo.com",     'role' => 'sales'],
            ['name' => 'Evan Marketing','email' => "marketing@demo.com", 'role' => 'marketing'],
        ];

        // Second org gets different emails
        if ($suffix !== 'a') {
            $roles = [
                ['name' => 'Fiona CEO',     'email' => "ceo@techgrowth.demo",  'role' => 'owner'],
                ['name' => 'George Dev',    'email' => "dev@techgrowth.demo",  'role' => 'admin'],
            ];
        }

        foreach ($roles as $r) {
            $id = DB::table('users')->insertGetId([
                'name'              => $r['name'],
                'email'             => $r['email'],
                'password'          => Hash::make('password'),
                'organization_id'   => $org['id'],
                'role'              => $r['role'],
                'email_verified_at' => now(),
                'created_at'        => now()->subMonths(5),
                'updated_at'        => now(),
            ]);
            $users[$r['role']] = ['id' => $id, 'name' => $r['name'], 'email' => $r['email']];
        }

        return $users;
    }

    // ─────────────────────────────────────────────────────────────
    // PROJECTS & KEYWORDS
    // ─────────────────────────────────────────────────────────────

    private function seedProjects(array $org, array $users): array
    {
        $projectDefs = [
            [
                'name'     => 'E-Commerce SEO Growth',
                'industry' => 'E-Commerce',
                'country'  => 'US',
                'location' => 'New York, NY',
                'keywords' => [
                    ['kw' => 'buy running shoes online', 'intent' => 'transactional', 'category' => 'product'],
                    ['kw' => 'best running shoes 2026',  'intent' => 'informational',  'category' => 'review'],
                    ['kw' => 'nike running shoes sale',  'intent' => 'transactional',  'category' => 'product'],
                    ['kw' => 'running shoes for women',  'intent' => 'transactional',  'category' => 'product'],
                    ['kw' => 'marathon training shoes',  'intent' => 'informational',  'category' => 'niche'],
                    ['kw' => 'trail running gear',       'intent' => 'commercial',     'category' => 'product'],
                    ['kw' => 'waterproof running shoes', 'intent' => 'commercial',     'category' => 'product'],
                ],
            ],
            [
                'name'     => 'SaaS Lead Generation',
                'industry' => 'SaaS / Technology',
                'country'  => 'US',
                'location' => 'San Francisco, CA',
                'keywords' => [
                    ['kw' => 'CRM software for small business', 'intent' => 'commercial',    'category' => 'saas'],
                    ['kw' => 'best project management tool',    'intent' => 'informational', 'category' => 'saas'],
                    ['kw' => 'marketing automation platform',   'intent' => 'commercial',    'category' => 'saas'],
                    ['kw' => 'sales pipeline software',         'intent' => 'commercial',    'category' => 'saas'],
                    ['kw' => 'email marketing tools 2026',      'intent' => 'informational', 'category' => 'saas'],
                    ['kw' => 'HubSpot alternative',             'intent' => 'commercial',    'category' => 'competitor'],
                ],
            ],
            [
                'name'     => 'Local Home Services',
                'industry' => 'Home Services',
                'country'  => 'US',
                'location' => 'Chicago, IL',
                'keywords' => [
                    ['kw' => 'plumber near me',        'intent' => 'local',          'category' => 'service'],
                    ['kw' => 'emergency plumbing',     'intent' => 'commercial',     'category' => 'service'],
                    ['kw' => 'bathroom renovation',    'intent' => 'commercial',     'category' => 'project'],
                    ['kw' => 'kitchen remodel cost',   'intent' => 'informational',  'category' => 'project'],
                    ['kw' => 'HVAC installation',      'intent' => 'commercial',     'category' => 'service'],
                ],
            ],
        ];

        $projects = [];
        foreach ($projectDefs as $def) {
            $projectId = DB::table('projects')->insertGetId([
                'organization_id'  => $org['id'],
                'name'             => $def['name'],
                'industry'         => $def['industry'],
                'country'          => $def['country'],
                'default_location' => $def['location'],
                'status'           => 'active',
                'created_at'       => now()->subMonths(4),
                'updated_at'       => now(),
            ]);

            $keywords = [];
            foreach ($def['keywords'] as $kw) {
                $growth7  = rand(-5, 85);
                $growth30 = rand(10, 120);
                $growth90 = rand(5, 200);
                $interest = rand(20, 95);
                $baseline = rand(15, 80);

                $state = 'stable';
                if ($growth7 >= 50) $state = 'rising';
                elseif ($growth7 >= 20) $state = 'growing';
                elseif ($growth7 < 0) $state = 'declining';

                $kwId = DB::table('keywords')->insertGetId([
                    'project_id'        => $projectId,
                    'keyword'           => $kw['kw'],
                    'normalized_keyword'=> strtolower(trim($kw['kw'])),
                    'category'          => $kw['category'],
                    'intent'            => $kw['intent'],
                    'priority'          => ['low','medium','high'][rand(0,2)],
                    'status'            => 'active',
                    'trend_state'       => $state,
                    'baseline_7d'       => $baseline,
                    'baseline_30d'      => $baseline + rand(5,20),
                    'baseline_90d'      => $baseline + rand(15,40),
                    'current_interest'  => $interest,
                    'growth_rate_7d'    => $growth7,
                    'growth_rate_30d'   => $growth30,
                    'growth_rate_90d'   => $growth90,
                    'volatility'        => rand(5,40) / 10,
                    'last_measured_at'  => now()->subHours(rand(1,24)),
                    'trend_updated_at'  => now()->subHours(rand(1,12)),
                    'created_at'        => now()->subMonths(4),
                    'updated_at'        => now(),
                ]);

                // Add keyword location
                DB::table('keyword_locations')->insert([
                    'keyword_id' => $kwId,
                    'country'    => $def['country'],
                    'region'     => explode(',', $def['location'])[1] ?? '',
                    'city'       => explode(',', $def['location'])[0],
                    'type'       => 'city',
                    'created_at' => now()->subMonths(4),
                    'updated_at' => now(),
                ]);

                $keywords[] = ['id' => $kwId, 'keyword' => $kw['kw']];
            }

            $projects[] = [
                'id'       => $projectId,
                'name'     => $def['name'],
                'keywords' => $keywords,
            ];
        }

        return $projects;
    }

    // ─────────────────────────────────────────────────────────────
    // KEYWORD MEASUREMENTS (90 days of history)
    // ─────────────────────────────────────────────────────────────

    private function seedKeywordMeasurements(array $projects): void
    {
        $rows = [];
        foreach ($projects as $project) {
            foreach (array_slice($project['keywords'], 0, 4) as $kw) { // 4 keywords × 30 days each
                $base    = rand(20, 75);
                $trend   = rand(-1, 3); // daily drift
                for ($d = 29; $d >= 0; $d--) {
                    $interest = max(1, min(100, $base + ($trend * (29 - $d)) + rand(-8, 8)));
                    $rows[] = [
                        'keyword_id'  => $kw['id'],
                        'source'      => 'google_trends',
                        'date'        => now()->subDays($d)->toDateString(),
                        'interest'    => $interest,
                        'volume'      => rand(500, 50000),
                        'growth'      => rand(-15, 60),
                        'competition' => rand(10, 95) / 100,
                        'cpc'         => rand(50, 500) / 100,
                        'geo'         => json_encode(['US' => rand(60, 100)]),
                        'raw_data'    => null,
                        'created_at'  => now()->subDays($d),
                        'updated_at'  => now()->subDays($d),
                    ];
                }
            }
        }
        // Chunk inserts to avoid memory issues
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('keyword_measurements')->insert($chunk);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // DEMAND CLUSTERS
    // ─────────────────────────────────────────────────────────────

    private function seedDemandClusters(array $projects): array
    {
        $clusters = [];
        $defs = [
            ['name' => 'Footwear Shopping Intent',   'category' => 'product',  'intent' => 'transactional'],
            ['name' => 'Sports Gear Research',        'category' => 'research', 'intent' => 'informational'],
            ['name' => 'SaaS Tool Evaluation',        'category' => 'saas',     'intent' => 'commercial'],
            ['name' => 'Local Service Emergency',     'category' => 'service',  'intent' => 'urgent'],
            ['name' => 'Home Renovation Planning',    'category' => 'project',  'intent' => 'informational'],
        ];

        foreach ($projects as $i => $project) {
            $def = $defs[$i] ?? $defs[0];
            $clusterId = DB::table('demand_clusters')->insertGetId([
                'project_id' => $project['id'],
                'name'       => $def['name'],
                'category'   => $def['category'],
                'intent'     => $def['intent'],
                'score'      => rand(55, 95),
                'status'     => 'active',
                'created_at' => now()->subMonths(3),
                'updated_at' => now(),
            ]);

            // Link first few keywords to cluster
            foreach (array_slice($project['keywords'], 0, 3) as $kw) {
                if (DB::getSchemaBuilder()->hasTable('cluster_keywords')) {
                    DB::table('cluster_keywords')->insert([
                        'cluster_id' => $clusterId,
                        'keyword_id' => $kw['id'],
                    ]);
                }
            }

            $clusters[] = ['id' => $clusterId, 'name' => $def['name'], 'project_id' => $project['id']];
        }

        return $clusters;
    }

    // ─────────────────────────────────────────────────────────────
    // OPPORTUNITIES
    // ─────────────────────────────────────────────────────────────

    private function seedOpportunities(array $org, array $projects, array $clusters): array
    {
        $defs = [
            [
                'title'       => 'Surging demand for premium running shoes in NYC',
                'status'      => 'actioned',
                'trend_state' => 'rising',
                'scores'      => [82, 78, 71, 85, 45, 62],
            ],
            [
                'title'       => 'Growing interest in trail running gear - Summer spike',
                'status'      => 'reviewed',
                'trend_state' => 'emerging',
                'scores'      => [68, 72, 65, 60, 55, 48],
            ],
            [
                'title'       => 'CRM software demand up 45% — SMBs switching providers',
                'status'      => 'detected',
                'trend_state' => 'rapidly_rising',
                'scores'      => [88, 91, 79, 82, 35, 70],
            ],
            [
                'title'       => 'Marketing automation searches spike Q3 2026',
                'status'      => 'actioned',
                'trend_state' => 'spike',
                'scores'      => [75, 85, 60, 70, 42, 55],
            ],
            [
                'title'       => 'Emergency plumbing searches up 60% Chicago area',
                'status'      => 'detected',
                'trend_state' => 'spike',
                'scores'      => [90, 88, 95, 77, 30, 80],
            ],
            [
                'title'       => 'Kitchen remodel intent growing steadily — fall season',
                'status'      => 'reviewed',
                'trend_state' => 'emerging',
                'scores'      => [62, 67, 70, 58, 50, 45],
            ],
            [
                'title'       => 'Women\'s running shoes — untapped transactional demand',
                'status'      => 'detected',
                'trend_state' => 'rising',
                'scores'      => [79, 82, 74, 88, 38, 55],
            ],
        ];

        $opportunities = [];
        foreach ($defs as $i => $def) {
            $project = $projects[$i % count($projects)];
            $cluster = $clusters[$i % count($clusters)] ?? null;
            $keyword = $project['keywords'][$i % count($project['keywords'])] ?? null;

            [$gs, $is, $geo, $vs, $cs, $hs] = $def['scores'];
            $oScore = round($gs * 0.30 + $is * 0.25 + $geo * 0.15 + $vs * 0.15 + $cs * 0.10 + $hs * 0.05, 1);

            $id = DB::table('opportunities')->insertGetId([
                'organization_id'    => $org['id'],
                'project_id'         => $project['id'],
                'keyword_id'         => $keyword['id'] ?? null,
                'cluster_id'         => $cluster['id'] ?? null,
                'location_id'        => null,
                'growth_score'       => $gs,
                'intent_score'       => $is,
                'geo_score'          => $geo,
                'volume_score'       => $vs,
                'competition_score'  => $cs,
                'historical_score'   => $hs,
                'opportunity_score'  => $oScore,
                'title'              => $def['title'],
                'explanation'        => "Demand signal detected via keyword trend analysis. Growth above baseline suggests emerging buyer intent in this segment.",
                'recommended_actions'=> json_encode([
                    'Create targeted landing page for this keyword cluster',
                    'Launch lead capture campaign within 72 hours',
                    'Brief sales team on opportunity profile',
                ]),
                'trend_state'        => $def['trend_state'],
                'status'             => $def['status'],
                'detected_at'        => now()->subDays(rand(1, 30)),
                'expires_at'         => now()->addDays(rand(15, 60)),
                'created_at'         => now()->subDays(rand(5, 45)),
                'updated_at'         => now(),
            ]);

            $opportunities[] = [
                'id'         => $id,
                'title'      => $def['title'],
                'project_id' => $project['id'],
                'score'      => $oScore,
            ];
        }

        return $opportunities;
    }

    // ─────────────────────────────────────────────────────────────
    // LANDING PAGES
    // ─────────────────────────────────────────────────────────────

    private function seedLandingPages(array $org, array $projects, array $opportunities): array
    {
        $defs = [
            [
                'title'  => 'Best Running Shoes for NYC Runners – Shop Now',
                'slug'   => 'running-shoes-nyc',
                'status' => 'published',
                'views'  => 2841,
            ],
            [
                'title'  => 'Top CRM Software for Small Teams – Free Trial',
                'slug'   => 'crm-small-business',
                'status' => 'published',
                'views'  => 5120,
            ],
            [
                'title'  => 'Emergency Plumber Chicago – 24/7 Service',
                'slug'   => 'emergency-plumber-chicago',
                'status' => 'published',
                'views'  => 1380,
            ],
            [
                'title'  => 'Marketing Automation for Growing Teams – Demo',
                'slug'   => 'marketing-automation-demo',
                'status' => 'draft',
                'views'  => 0,
            ],
            [
                'title'  => 'Trail Running Gear Guide 2026 – Expert Picks',
                'slug'   => 'trail-running-gear-guide',
                'status' => 'published',
                'views'  => 893,
            ],
        ];

        $pages = [];
        foreach ($defs as $i => $def) {
            $opp     = $opportunities[$i] ?? $opportunities[0];
            $project = $projects[$i % count($projects)];
            $convs   = (int) ($def['views'] * (rand(2, 8) / 100));

            $id = DB::table('landing_pages')->insertGetId([
                'organization_id' => $org['id'],
                'project_id'      => $project['id'],
                'opportunity_id'  => $opp['id'],
                'slug'            => $def['slug'],
                'title'           => $def['title'],
                'content'         => json_encode([
                    'hero'     => ['headline' => $def['title'], 'subheadline' => 'Powered by demand intelligence.'],
                    'benefits' => ['Targeted to current demand', 'AI-optimised copy', 'High-converting layout'],
                    'cta'      => 'Get Started Free',
                ]),
                'template'        => 'default',
                'meta'            => json_encode(['title' => $def['title'], 'description' => 'Landing page for '.$def['title']]),
                'status'          => $def['status'],
                'views'           => $def['views'],
                'conversions'     => $convs,
                'published_at'    => $def['status'] === 'published' ? now()->subDays(rand(3, 30)) : null,
                'created_at'      => now()->subDays(rand(5, 40)),
                'updated_at'      => now(),
            ]);

            $pages[] = ['id' => $id, 'title' => $def['title']];
        }

        return $pages;
    }

    // ─────────────────────────────────────────────────────────────
    // LEADS & CONTACTS
    // ─────────────────────────────────────────────────────────────

    private function seedLeadsAndContacts(array $org, array $projects, array $opportunities, array $landingPages, array $users): array
    {
        $salesUser = $users['sales'] ?? array_values($users)[0];
        $mktUser   = $users['marketing'] ?? array_values($users)[0];

        $leadDefs = [
            // Hot leads
            ['first' => 'Jennifer', 'last' => 'Walsh',    'company' => 'Walsh Retail',      'email' => 'jennifer.walsh@walshretail.com',  'status' => 'qualified',   'score' => 92, 'source' => 'landing_page'],
            ['first' => 'Marcus',   'last' => 'Chen',     'company' => 'TechStartup Inc',   'email' => 'mchen@techstartup.io',            'status' => 'contacted',   'score' => 88, 'source' => 'organic'],
            ['first' => 'Sarah',    'last' => 'Patterson', 'company' => 'Patterson Group',  'email' => 'sarah@pattersongroup.com',        'status' => 'qualified',   'score' => 85, 'source' => 'referral'],
            // Warm leads
            ['first' => 'David',    'last' => 'Torres',   'company' => 'Torres Plumbing',   'email' => 'david@torresplumbing.com',        'status' => 'new',         'score' => 74, 'source' => 'landing_page'],
            ['first' => 'Amy',      'last' => 'Nguyen',   'company' => 'Nguyen E-commerce', 'email' => 'amy.nguyen@nguyenshop.com',       'status' => 'contacted',   'score' => 71, 'source' => 'organic'],
            ['first' => 'Robert',   'last' => 'Kim',      'company' => 'Kim Solutions',     'email' => 'robert.kim@kimsolutions.co',      'status' => 'new',         'score' => 68, 'source' => 'campaign'],
            ['first' => 'Lisa',     'last' => 'Anderson', 'company' => 'Anderson Homes',    'email' => 'lisa.anderson@andersonhomes.us',  'status' => 'qualified',   'score' => 77, 'source' => 'landing_page'],
            // Potential leads
            ['first' => 'James',    'last' => 'Patel',    'company' => 'Patel Ventures',    'email' => 'j.patel@patelventures.in',        'status' => 'new',         'score' => 58, 'source' => 'organic'],
            ['first' => 'Emma',     'last' => 'Johnson',  'company' => 'Johnson Media',     'email' => 'emma@johnsonmedia.com',           'status' => 'new',         'score' => 55, 'source' => 'social'],
            ['first' => 'Carlos',   'last' => 'Rivera',   'company' => 'Rivera Services',   'email' => 'carlos@riveraservices.com',       'status' => 'contacted',   'score' => 62, 'source' => 'paid'],
            ['first' => 'Natalie',  'last' => 'Brooks',   'company' => 'Brooks Consulting', 'email' => 'nbrooks@brooksconsulting.com',    'status' => 'new',         'score' => 48, 'source' => 'organic'],
            ['first' => 'Tyler',    'last' => 'Scott',    'company' => 'Scott Industries',  'email' => 'tyler.scott@scottind.com',        'status' => 'new',         'score' => 51, 'source' => 'campaign'],
        ];

        $leads    = [];
        $contacts = [];

        foreach ($leadDefs as $i => $def) {
            $opp     = $opportunities[$i % count($opportunities)];
            $project = $projects[$i % count($projects)];
            $lp      = $def['source'] === 'landing_page' ? ($landingPages[$i % count($landingPages)] ?? null) : null;
            $score   = $def['score'];

            $scoreLabel = $score >= 90 ? 'hot' : ($score >= 70 ? 'warm' : ($score >= 40 ? 'potential' : 'low'));

            $leadId = DB::table('leads')->insertGetId([
                'organization_id'     => $org['id'],
                'project_id'          => $project['id'],
                'opportunity_id'      => $opp['id'],
                'campaign_id'         => null,
                'name'                => $def['first'] . ' ' . $def['last'],
                'email'               => $def['email'],
                'phone'               => '+1' . rand(200,999) . rand(1000000,9999999),
                'location'            => ['New York, NY','San Francisco, CA','Chicago, IL','Austin, TX','Seattle, WA'][rand(0,4)],
                'company'             => $def['company'],
                'source'              => $def['source'],
                'intent'              => ['informational','commercial','transactional','unknown'][rand(0, 3)],
                'lead_score'          => $score,
                'score_label'         => $scoreLabel,
                'score_breakdown'     => json_encode([
                    'intent' => rand(60,95), 'engagement' => rand(50,90),
                    'location_fit' => rand(55,90), 'product_fit' => rand(45,85),
                    'budget_fit' => rand(40,80), 'recency' => rand(50,95),
                ]),
                'score_explanation'   => "Score based on intent signals, engagement history, and company profile.",
                'scored_at'           => now()->subDays(rand(1,14)),
                'budget_range'        => ['$500-$1k','$1k-$5k','$5k-$20k','$20k+'][rand(0,3)],
                'company_size'        => ['1-10','11-50','51-200','201-500','500+'][rand(0,4)],
                'message'             => "Interested in your solution. Saw your content and would like to learn more.",
                'status'              => $def['status'],
                'assigned_to'         => rand(0,1) ? $salesUser['id'] : null,
                'contacted_at'        => in_array($def['status'], ['contacted','qualified']) ? now()->subDays(rand(1,10)) : null,
                'qualified_at'        => $def['status'] === 'qualified' ? now()->subDays(rand(1,7)) : null,
                'created_at'          => now()->subDays(rand(2, 60)),
                'updated_at'          => now(),
            ]);

            $leads[] = ['id' => $leadId, 'name' => $def['first'] . ' ' . $def['last'], 'email' => $def['email']];

            // Create lead event
            DB::table('lead_events')->insert([
                'lead_id'     => $leadId,
                'type'        => 'created',
                'metadata'    => json_encode(['source' => $def['source']]),
                'occurred_at' => now()->subDays(rand(2, 60)),
                'created_at'  => now()->subDays(rand(2, 60)),
                'updated_at'  => now(),
            ]);

            // Create contact for qualified/contacted leads
            if (in_array($def['status'], ['qualified', 'contacted'])) {
                $contactId = DB::table('contacts')->insertGetId([
                    'organization_id' => $org['id'],
                    'lead_id'         => $leadId,
                    'name'            => $def['first'] . ' ' . $def['last'],
                    'email'           => $def['email'],
                    'phone'           => '+1' . rand(200,999) . rand(1000000,9999999),
                    'company'         => $def['company'],
                    'tags'            => json_encode([$def['source'], $scoreLabel]),
                    'created_at'      => now()->subDays(rand(1, 30)),
                    'updated_at'      => now(),
                ]);
                $contacts[] = ['id' => $contactId, 'lead_id' => $leadId, 'name' => $def['first'] . ' ' . $def['last']];
            }
        }

        return [$leads, $contacts];
    }

    // ─────────────────────────────────────────────────────────────
    // DEALS
    // ─────────────────────────────────────────────────────────────

    private function seedDeals(array $org, array $leads, array $contacts, array $users): array
    {
        $salesUser = $users['sales'] ?? array_values($users)[0];
        $adminUser = $users['admin'] ?? array_values($users)[0];

        $dealDefs = [
            ['title' => 'Running Shoes Partnership — Walsh Retail',  'value' => 12500, 'stage' => 'negotiation', 'status' => 'open',  'close_days' => 14],
            ['title' => 'CRM Platform License — TechStartup Inc',    'value' => 8400,  'stage' => 'quotation',   'status' => 'open',  'close_days' => 21],
            ['title' => 'Marketing Automation — Patterson Group',     'value' => 15000, 'stage' => 'qualified',   'status' => 'won',   'close_days' => -5],
            ['title' => 'Emergency Plumbing Contract — Torres',       'value' => 3200,  'stage' => 'new',         'status' => 'open',  'close_days' => 30],
            ['title' => 'E-comm SEO Package — Nguyen Store',          'value' => 5800,  'stage' => 'quotation',   'status' => 'open',  'close_days' => 10],
            ['title' => 'Lead Gen Suite — Kim Solutions',             'value' => 6700,  'stage' => 'qualified',   'status' => 'open',  'close_days' => 45],
            ['title' => 'Home Services Growth Plan — Anderson',       'value' => 4500,  'stage' => 'negotiation', 'status' => 'open',  'close_days' => 7],
            ['title' => 'Annual SaaS Subscription — Patel Ventures',  'value' => 9900,  'stage' => 'lost',        'status' => 'lost',  'close_days' => -10],
        ];

        $deals = [];
        foreach ($dealDefs as $i => $def) {
            $lead    = $leads[$i] ?? $leads[0];
            $contact = $contacts[$i % max(1, count($contacts))] ?? null;
            $assigned = $i % 2 === 0 ? $salesUser['id'] : $adminUser['id'];

            $id = DB::table('deals')->insertGetId([
                'organization_id'   => $org['id'],
                'lead_id'           => $lead['id'],
                'contact_id'        => $contact['id'] ?? null,
                'title'             => $def['title'],
                'value'             => $def['value'],
                'currency'          => 'USD',
                'stage'             => $def['stage'],
                'status'            => $def['status'],
                'assigned_to'       => $assigned,
                'lost_reason'       => $def['status'] === 'lost' ? 'Budget constraints — chose a cheaper competitor.' : null,
                'expected_close_at' => now()->addDays($def['close_days']),
                'won_at'            => $def['status'] === 'won' ? now()->subDays(5) : null,
                'lost_at'           => $def['status'] === 'lost' ? now()->subDays(10) : null,
                'created_at'        => now()->subDays(rand(7, 45)),
                'updated_at'        => now(),
            ]);

            $deals[] = ['id' => $id, 'title' => $def['title'], 'lead_id' => $lead['id']];
        }

        return $deals;
    }

    // ─────────────────────────────────────────────────────────────
    // TASKS
    // ─────────────────────────────────────────────────────────────

    private function seedTasks(array $org, array $deals, array $leads, array $users): void
    {
        $salesUser = $users['sales'] ?? array_values($users)[0];
        $adminUser = $users['admin'] ?? array_values($users)[0];

        $taskDefs = [
            ['title' => 'Follow up on proposal — Walsh Retail',       'type' => 'follow_up', 'status' => 'pending',     'days' => 1],
            ['title' => 'Schedule product demo — TechStartup',        'type' => 'meeting',   'status' => 'pending',     'days' => 2],
            ['title' => 'Send pricing sheet to Patterson Group',       'type' => 'email',     'status' => 'completed',   'days' => -3],
            ['title' => 'Call Torres about contract details',          'type' => 'call',      'status' => 'pending',     'days' => 0],
            ['title' => 'Prepare Q3 deal review deck',                'type' => 'other',     'status' => 'in_progress', 'days' => 3],
            ['title' => 'Research Nguyen competitors',                 'type' => 'other',     'status' => 'pending',     'days' => 5],
            ['title' => 'Onboarding call — Patterson (new client)',    'type' => 'meeting',   'status' => 'pending',     'days' => 7],
            ['title' => 'Update CRM notes for Kim Solutions',         'type' => 'other',     'status' => 'pending',     'days' => -1],
            ['title' => 'Send NDA to Anderson Homes',                  'type' => 'email',     'status' => 'pending',     'days' => 1],
            ['title' => 'Discovery call — Patel Ventures',             'type' => 'call',      'status' => 'completed',   'days' => -7],
        ];

        foreach ($taskDefs as $i => $def) {
            $deal   = $deals[$i % count($deals)] ?? null;
            $lead   = $leads[$i % count($leads)] ?? null;
            $user   = $i % 2 === 0 ? $salesUser : $adminUser;

            DB::table('tasks')->insert([
                'organization_id'  => $org['id'],
                'lead_id'          => $lead['id'] ?? null,
                'deal_id'          => $deal['id'] ?? null,
                'assigned_user_id' => $user['id'],
                'title'            => $def['title'],
                'description'      => 'Task auto-created from demo data. Review before action.',
                'type'             => $def['type'],
                'status'           => $def['status'],
                'due_at'           => now()->addDays($def['days']),
                'completed_at'     => $def['status'] === 'completed' ? now()->subDays(rand(1,5)) : null,
                'created_at'       => now()->subDays(rand(1, 20)),
                'updated_at'       => now(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // NOTES
    // ─────────────────────────────────────────────────────────────

    private function seedNotes(array $org, array $deals, array $leads, array $users): void
    {
        $salesUser = $users['sales'] ?? array_values($users)[0];
        $adminUser = $users['admin'] ?? array_values($users)[0];

        $noteDefs = [
            "Initial call went well — client is interested in the Growth plan. Budget confirmed at $15k/year. Next step: send proposal.",
            "Follow-up email sent. Client mentioned they're evaluating 2 other vendors. Decision expected within 2 weeks.",
            "Demo completed successfully. Client particularly liked the keyword trend feature and lead scoring. Strong buying signal.",
            "Contract signed! Onboarding scheduled for next Tuesday. Introductions made with their team lead.",
            "Pricing objection — they want 20% discount. Escalated to manager for approval.",
            "Client emailed asking about API integrations and custom reporting. Forwarded to technical team.",
            "Quarterly review completed. Client is happy with leads quality — 3 deals closed from our opportunities this month.",
        ];

        foreach ($noteDefs as $i => $note) {
            $deal = $deals[$i % count($deals)] ?? null;
            $lead = $leads[$i % count($leads)] ?? null;
            $user = $i % 2 === 0 ? $salesUser : $adminUser;

            DB::table('notes')->insert([
                'organization_id' => $org['id'],
                'lead_id'         => $lead['id'] ?? null,
                'deal_id'         => $deal['id'] ?? null,
                'user_id'         => $user['id'],
                'body'            => $note,
                'created_at'      => now()->subDays(rand(1, 30)),
                'updated_at'      => now(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // ALERT RULES & ALERTS
    // ─────────────────────────────────────────────────────────────

    private function seedAlerts(array $org, array $projects, array $opportunities, array $leads): void
    {
        $ruleIds = [];
        $ruleDefs = [
            [
                'name'     => 'High Growth Keyword Alert',
                'min_score'=> 70,
                'min_growth'=> 40,
                'channels' => ['email'],
            ],
            [
                'name'     => 'Transactional Intent Spike',
                'min_score'=> 60,
                'min_growth'=> 25,
                'channels' => ['email', 'sms'],
            ],
            [
                'name'     => 'New Opportunity Detected',
                'min_score'=> 50,
                'min_growth'=> 15,
                'channels' => ['email'],
            ],
        ];

        foreach ($ruleDefs as $i => $def) {
            $project = $projects[$i % count($projects)];
            $id = DB::table('alert_rules')->insertGetId([
                'organization_id' => $org['id'],
                'project_id'      => $project['id'],
                'name'            => $def['name'],
                'minimum_score'   => $def['min_score'],
                'minimum_growth'  => $def['min_growth'],
                'intent'          => 'any',
                'location'        => null,
                'cooldown'        => 24,
                'channels'        => json_encode($def['channels']),
                'recipients'      => json_encode(['owner@demo.com', 'admin@demo.com']),
                'quiet_hours'     => json_encode(['start' => '22:00', 'end' => '07:00']),
                'status'          => 'active',
                'created_at'      => now()->subMonths(2),
                'updated_at'      => now(),
            ]);
            $ruleIds[] = $id;
        }

        // Fire some alert records
        $alertMessages = [
            "🚀 Running shoes demand is up 65% — opportunity score 82/100",
            "📈 CRM software search volume spiked 45% in last 7 days",
            "⚡ Emergency plumbing searches surged — Chicago market opportunity detected",
            "🎯 Marketing automation intent signals rising — 3 hot keywords detected",
        ];

        foreach ($alertMessages as $i => $msg) {
            $opp  = $opportunities[$i % count($opportunities)];
            $lead = $leads[$i % count($leads)];

            DB::table('alerts')->insert([
                'organization_id' => $org['id'],
                'opportunity_id'  => $opp['id'],
                'lead_id'         => null,
                'alert_rule_id'   => $ruleIds[$i % count($ruleIds)],
                'type'            => 'opportunity',
                'channel'         => ['email', 'sms'][rand(0, 1)],
                'recipient'       => 'owner@demo.com',
                'message'         => $msg,
                'payload'         => json_encode(['opportunity_id' => $opp['id'], 'score' => $opp['score']]),
                'status'          => ['sent', 'sent', 'sent', 'pending'][rand(0,3)],
                'sent_at'         => now()->subHours(rand(1,48)),
                'created_at'      => now()->subDays(rand(1, 14)),
                'updated_at'      => now(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // EMAIL TEMPLATES & CAMPAIGNS
    // ─────────────────────────────────────────────────────────────

    private function seedEmailCampaigns(array $org, array $opportunities, array $leads, array $users): void
    {
        $mktUser = $users['marketing'] ?? array_values($users)[0];

        // Email Templates
        $templateDefs = [
            [
                'name'        => 'Welcome New Lead',
                'category'    => 'welcome',
                'subject'     => 'Welcome to {{company_name}} — Let\'s get started',
                'html_content'=> '<h2>Hi {{first_name}},</h2><p>Thanks for your interest! Our team will reach out within 24 hours.</p><p>In the meantime, explore how we help businesses like <strong>{{company}}</strong> find demand opportunities.</p><p>Best,<br>{{from_name}}</p>',
                'variables'   => ['first_name', 'company', 'company_name', 'from_name'],
            ],
            [
                'name'        => 'Opportunity Detected — Action Required',
                'category'    => 'opportunity',
                'subject'     => '🚀 New demand spike detected: {{opportunity_title}}',
                'html_content'=> '<h2>New Opportunity Detected</h2><p>Hi {{first_name}},</p><p>Our system has identified a high-value demand signal: <strong>{{opportunity_title}}</strong></p><p>Score: <strong>{{score}}/100</strong> — Growth: <strong>{{growth_percentage}}</strong></p><p><a href="{{cta_url}}">View Opportunity →</a></p>',
                'variables'   => ['first_name', 'opportunity_title', 'score', 'growth_percentage', 'cta_url'],
            ],
            [
                'name'        => 'Follow Up — No Response',
                'category'    => 'follow_up',
                'subject'     => 'Quick check-in, {{first_name}} 👋',
                'html_content'=> '<p>Hi {{first_name}},</p><p>I wanted to follow up on our conversation about {{company}}\'s growth goals.</p><p>Are you still looking to capture demand in your market? Happy to jump on a 15-minute call.</p><p>Best,<br>{{from_name}}</p>',
                'variables'   => ['first_name', 'company', 'from_name'],
            ],
            [
                'name'        => 'Lead Nurture — Case Study',
                'category'    => 'nurture',
                'subject'     => 'How {{similar_company}} grew 3x with demand intelligence',
                'html_content'=> '<h2>Case Study: 3x Growth in 6 Months</h2><p>Hi {{first_name}},</p><p>Here\'s how a company similar to {{company}} used demand intelligence to triple their pipeline.</p><p><a href="{{cta_url}}">Read the full story →</a></p>',
                'variables'   => ['first_name', 'company', 'similar_company', 'cta_url'],
            ],
        ];

        $templateIds = [];
        foreach ($templateDefs as $def) {
            $id = DB::table('email_templates')->insertGetId([
                'organization_id' => $org['id'],
                'created_by'      => $mktUser['id'],
                'name'            => $def['name'],
                'description'     => 'Demo template — '.$def['category'],
                'category'        => $def['category'],
                'subject'         => $def['subject'],
                'preview_text'    => 'Preview: ' . Str::limit(strip_tags($def['html_content']), 80),
                'html_content'    => $def['html_content'],
                'text_content'    => strip_tags($def['html_content']),
                'variables'       => json_encode($def['variables']),
                'is_system'       => false,
                'is_active'       => true,
                'usage_count'     => rand(2, 15),
                'last_used_at'    => now()->subDays(rand(1, 14)),
                'created_at'      => now()->subMonths(2),
                'updated_at'      => now(),
            ]);
            $templateIds[] = $id;
        }

        // Email Campaigns
        $campaignDefs = [
            [
                'name'      => 'Q3 Demand Opportunity Blast',
                'status'    => 'sent',
                'audience'  => 'all_leads',
                'total'     => 85,
                'delivered' => 82,
                'opened'    => 38,
                'clicked'   => 14,
                'bounced'   => 3,
            ],
            [
                'name'      => 'September Lead Nurture Sequence',
                'status'    => 'sending',
                'audience'  => 'segment',
                'total'     => 42,
                'delivered' => 30,
                'opened'    => 12,
                'clicked'   => 5,
                'bounced'   => 0,
            ],
            [
                'name'      => 'New Feature Announcement — AI Scoring',
                'status'    => 'scheduled',
                'audience'  => 'all_leads',
                'total'     => 120,
                'delivered' => 0,
                'opened'    => 0,
                'clicked'   => 0,
                'bounced'   => 0,
            ],
            [
                'name'      => 'Emergency Plumbing Chicago — Local Outreach',
                'status'    => 'draft',
                'audience'  => 'opportunity',
                'total'     => 0,
                'delivered' => 0,
                'opened'    => 0,
                'clicked'   => 0,
                'bounced'   => 0,
            ],
        ];

        foreach ($campaignDefs as $i => $def) {
            $opp        = $opportunities[$i % count($opportunities)];
            $templateId = $templateIds[$i % count($templateIds)];

            $campaignId = DB::table('email_campaigns')->insertGetId([
                'organization_id'     => $org['id'],
                'template_id'         => $templateId,
                'opportunity_id'      => $opp['id'],
                'created_by'          => $mktUser['id'],
                'name'                => $def['name'],
                'subject'             => 'Subject for ' . $def['name'],
                'preview_text'        => 'Preview text for this campaign...',
                'html_content'        => '<p>Campaign email content for <strong>' . $def['name'] . '</strong></p>',
                'text_content'        => 'Campaign email content for ' . $def['name'],
                'status'              => $def['status'],
                'audience_type'       => $def['audience'],
                'audience_filters'    => null,
                'scheduled_at'        => $def['status'] === 'scheduled' ? now()->addDays(2) : null,
                'started_at'          => in_array($def['status'], ['sending','sent']) ? now()->subDays(rand(1,7)) : null,
                'completed_at'        => $def['status'] === 'sent' ? now()->subDays(rand(1,3)) : null,
                'total_recipients'    => $def['total'],
                'sent_count'          => $def['delivered'],
                'delivered_count'     => $def['delivered'],
                'opened_count'        => $def['opened'],
                'clicked_count'       => $def['clicked'],
                'bounced_count'       => $def['bounced'],
                'unsubscribed_count'  => rand(0, 2),
                'complained_count'    => 0,
                'from_name'           => 'Acme Marketing Team',
                'from_email'          => 'campaigns@demo.com',
                'reply_to'            => 'owner@demo.com',
                'track_opens'         => true,
                'track_clicks'        => true,
                'created_at'          => now()->subDays(rand(5, 30)),
                'updated_at'          => now(),
            ]);

            // Add campaign recipients from leads
            if ($def['total'] > 0) {
                $recipientRows = [];
                foreach (array_slice($leads, 0, min($def['total'], count($leads))) as $lead) {
                    $status = $def['status'] === 'sent' ? 'delivered' : 'pending';
                    if ($def['status'] === 'sent' && rand(0, 1)) $status = 'opened';
                    $recipientRows[] = [
                        'campaign_id'    => $campaignId,
                        'lead_id'        => $lead['id'],
                        'email'          => $lead['email'],
                        'first_name'     => explode(' ', $lead['name'])[0] ?? '',
                        'last_name'      => explode(' ', $lead['name'])[1] ?? '',
                        'status'         => $status,
                        'sent_at'        => $def['status'] !== 'draft' ? now()->subDays(rand(1,5)) : null,
                        'delivered_at'   => $def['delivered'] > 0 ? now()->subDays(rand(1,4)) : null,
                        'opened_at'      => $status === 'opened' ? now()->subDays(rand(0,3)) : null,
                        'open_count'     => $status === 'opened' ? rand(1, 3) : 0,
                        'click_count'    => 0,
                        'created_at'     => now()->subDays(rand(1, 10)),
                        'updated_at'     => now(),
                    ];
                }
                if (!empty($recipientRows)) {
                    foreach (array_chunk($recipientRows, 50) as $chunk) {
                        DB::table('campaign_recipients')->insert($chunk);
                    }
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // AUDIT LOGS
    // ─────────────────────────────────────────────────────────────

    private function seedAuditLogs(array $org, array $users): void
    {
        $ownerUser = $users['owner'] ?? array_values($users)[0];
        $adminUser = $users['admin'] ?? array_values($users)[0];

        $logs = [
            ['action' => 'user.login',           'description' => 'User logged in',                            'user' => $ownerUser],
            ['action' => 'project.created',      'description' => 'Created project: E-Commerce SEO Growth',    'user' => $ownerUser],
            ['action' => 'keyword.added',         'description' => 'Added 7 keywords to project',              'user' => $adminUser],
            ['action' => 'opportunity.actioned',  'description' => 'Opportunity marked as actioned',            'user' => $adminUser],
            ['action' => 'lead.qualified',        'description' => 'Lead Jennifer Walsh qualified',             'user' => $users['sales'] ?? $adminUser],
            ['action' => 'deal.created',          'description' => 'Deal created: Walsh Retail partnership',    'user' => $users['sales'] ?? $adminUser],
            ['action' => 'campaign.sent',         'description' => 'Email campaign sent to 85 recipients',      'user' => $users['marketing'] ?? $adminUser],
            ['action' => 'alert_rule.created',    'description' => 'Alert rule created: High Growth Keyword',  'user' => $adminUser],
            ['action' => 'landing_page.published','description' => 'Landing page published: running-shoes-nyc','user' => $users['marketing'] ?? $adminUser],
            ['action' => 'user.login',            'description' => 'User logged in',                           'user' => $users['sales'] ?? $adminUser],
        ];

        foreach ($logs as $log) {
            DB::table('audit_logs')->insert([
                'organization_id' => $org['id'],
                'user_id'         => $log['user']['id'],
                'action'          => $log['action'],
                'entity_type'     => 'system',
                'entity_id'       => null,
                'metadata'        => json_encode(['description' => $log['description']]),
                'ip_address'      => '127.0.0.' . rand(1, 50),
                'created_at'      => now()->subDays(rand(0, 30)),
            ]);
        }
    }
}
