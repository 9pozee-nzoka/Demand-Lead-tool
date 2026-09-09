# DemandLead - AI Demand Intelligence Platform

**Version**: 1.0.0  
**Status**: 90% MVP Complete

---

## What is DemandLead?

An AI-powered SaaS platform that detects rising market demand, scores business opportunities, generates leads automatically, and manages your sales pipeline.

**Core Flow**: Detect Demand → Score Opportunities → Generate Leads → Convert to Revenue

---

## Features

### ✅ Completed
- 🔐 **Multi-Tenant Architecture**: Secure organization-level data isolation
- 🔑 **Authentication & RBAC**: 6 role levels (owner → viewer) with Laravel Sanctum
- 📊 **Demand Intelligence**: Keyword tracking, trend detection, baseline analytics
- 🎯 **Opportunity Scoring**: 0-100 explainable scores with 6-factor algorithm
- 🚀 **Lead Management**: Capture, qualify, score, and route leads
- 💼 **CRM Pipeline**: Deals, tasks, notes, and pipeline management
- 📧 **Email Campaigns**: Template builder with audience targeting
- 🌐 **Landing Pages**: AI-powered page generation with analytics
- 🔔 **Alert System**: Rule-based notifications (email, SMS ready)
- 🛡️ **Super Admin Panel**: System-wide monitoring and management
- 🔒 **Two-Factor Auth**: TOTP-based 2FA for enhanced security
- 📈 **Analytics Dashboard**: Comprehensive metrics and reporting

### 🔨 In Progress
- Google Trends API integration (Sprint 4)
- Demand signal processing (Sprint 5)
- Opportunity auto-creation (Sprint 6)
- WhatsApp integration (Sprint 10)

---

## Quick Start

### Prerequisites
- PHP 8.3+
- MySQL/MariaDB
- Composer
- Node.js (for asset compilation)

### Installation

```bash
cd Backend

# Install dependencies
composer install
npm install && npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate
php artisan db:seed --class=DemoDataSeeder

# Start development server
php artisan serve
```

Visit: `http://localhost:8000`

**Demo Credentials:**
```
Owner     : owner@demo.com      / password
Admin     : admin@demo.com      / password
Sales     : sales@demo.com      / password
```

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Blade Templates + Tailwind CSS + Alpine.js |
| Database | MySQL (SQLite for local dev) |
| Cache/Queue | Redis (database queue for dev) |
| AI | OpenAI API |
| SMS | Africa's Talking (configured, not active) |
| Messaging | WhatsApp Business Platform (planned) |

---

## Architecture

```
┌─────────────────────────────────────────────┐
│    Web Interface (Blade + Tailwind)         │
├─────────────────────────────────────────────┤
│  Controllers → Services → Jobs → Models     │
├─────────────────────────────────────────────┤
│  Queue Workers | Scheduler | Cache          │
├─────────────────────────────────────────────┤
│  MySQL Database | Audit Logs                │
├─────────────────────────────────────────────┤
│  External APIs (Google Trends, OpenAI, SMS) │
└─────────────────────────────────────────────┘
```

**Multi-Tenancy**: Organization-scoped queries with `EnsureSameTenant` middleware

**Queue System**:
- `ingestion` - Data collection
- `processing` - Trend analysis
- `scoring` - Opportunity calculation
- `notifications` - Alerts
- `lead_workflows` - Lead qualification

---

## Key Commands

### Development
```bash
# Start server
php artisan serve

# Process queues
php artisan queue:work --queue=ingestion,processing,scoring

# Run scheduler (for background tasks)
php artisan schedule:work
```

### Data Collection (Sprint 4)
```bash
# Collect keyword data
php artisan keywords:collect --sync --id=1

# Check system health
php artisan demand:health

# Manual demand collection
php artisan demand:collect
```

### Database
```bash
# Fresh migration + demo data
php artisan migrate:fresh --seed

# Seed demo data only
php artisan db:seed --class=DemoDataSeeder
```

### Administration
```bash
# Promote user to super admin
php artisan admin:promote owner@demo.com

# Demote super admin
php artisan admin:demote owner@demo.com
```

---

## Project Structure

```
Backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/      # REST API endpoints
│   │   ├── Controllers/Web/      # Blade view controllers
│   │   ├── Middleware/           # Multi-tenancy, RBAC
│   │   └── Requests/             # Form validation
│   ├── Models/                   # Eloquent models (35+)
│   ├── Services/                 # Business logic
│   │   ├── Demand/              # Trend detection, baselines
│   │   ├── Intelligence/        # AI enrichment
│   │   ├── Opportunities/       # Scoring engine
│   │   ├── Leads/               # Lead management
│   │   ├── Alerts/              # Rule evaluation
│   │   └── Providers/           # Google Trends, etc.
│   ├── Jobs/                     # Queue jobs
│   └── Helpers/                  # Utilities
├── resources/views/              # Blade templates (40+)
├── routes/
│   ├── web.php                  # Web routes
│   ├── api.php                  # REST API
│   └── console.php              # Scheduled tasks
└── database/
    ├── migrations/              # Schema (20+ tables)
    └── seeders/                 # Demo data
```

---

## Database Schema

**Core Tables** (20+):
- `organizations`, `users`, `subscriptions`, `plans`
- `projects`, `keywords`, `keyword_locations`, `keyword_measurements`
- `demand_clusters`, `opportunities`
- `leads`, `lead_events`, `contacts`
- `deals`, `tasks`, `notes`
- `landing_pages`, `email_campaigns`, `campaign_recipients`
- `alerts`, `alert_rules`, `audit_logs`

See `.kiro/steering/architecture.md` for complete schema documentation.

---

## Configuration

### Required Environment Variables
```env
APP_KEY=base64:...                    # Generated by artisan key:generate
DB_DATABASE=demand_lead
DB_USERNAME=root
DB_PASSWORD=
```

### Optional (Feature Flags)
```env
# Google Trends (Sprint 4)
SERPAPI_KEY=your_key                  # Or leave empty for synthetic data

# AI Features (Sprint 5+)
OPENAI_API_KEY=your_key
OPENAI_MODEL=gpt-4o-mini

# SMS Alerts (Sprint 9)
AT_API_KEY=your_key
AT_USERNAME=your_username

# WhatsApp (Sprint 10)
WHATSAPP_ACCESS_TOKEN=your_token
WHATSAPP_PHONE_NUMBER_ID=your_id
```

---

## Security

- ✅ Multi-tenant data isolation with `organization_id` scoping
- ✅ Role-based access control (6 roles: owner, admin, analyst, marketing, sales, viewer)
- ✅ Laravel Sanctum token authentication
- ✅ CSRF protection on all forms
- ✅ SQL injection prevention (parameterized queries)
- ✅ XSS protection (Blade escaping)
- ✅ Rate limiting (configured, needs Redis for production)
- ✅ Audit logging for all critical actions
- ✅ Encrypted credential storage
- ✅ Two-factor authentication (TOTP)

---

## Current Sprint Status

**Sprint 1-2**: ✅ Complete (Auth, multi-tenancy)  
**Sprint 3**: ✅ Complete (Projects, keywords, locations)  
**Sprint 4**: 🔨 In Progress (Google Trends integration)  
**Sprint 5-12**: 🔲 Planned (Signal processing, opportunities, alerts, CRM)

---

## Roadmap

### Next 30 Days
- [ ] Complete Sprint 4: Google Trends data collection
- [ ] Sprint 5: Process demand signals (baselines, trend states)
- [ ] Sprint 6: Opportunity scoring automation
- [ ] Sprint 7: Alert rules and notifications
- [ ] Sprint 8: AI explanations and insights

### Next 90 Days
- [ ] Sprint 9: SMS alerts via Africa's Talking
- [ ] Sprint 10: WhatsApp lead capture
- [ ] Sprint 11: Basic CRM enhancements
- [ ] Sprint 12: Production deployment (Docker + Nginx)

---

## Performance

- Response Time: <500ms (development)
- Database Queries: Optimized with eager loading
- Queue Processing: Async job handling
- Caching: Ready for Redis in production

---

## License

Proprietary - All rights reserved

---

## Support

For issues or questions, check:
- Architecture documentation: `.kiro/steering/architecture.md`
- Backend README: `Backend/README.md`

---

**Built with Laravel 11, Tailwind CSS, and modern PHP best practices.**
