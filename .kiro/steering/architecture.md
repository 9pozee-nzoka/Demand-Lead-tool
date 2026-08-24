# AI Demand Intelligence SaaS — Architecture Reference

This steering file gives Kiro the full context needed to continue building this project correctly.

---

## Product Summary

A **Demand-to-Lead Intelligence Platform** that detects rising market demand, scores it as a business opportunity, generates leads, routes them through a CRM pipeline, and measures revenue.

**Core loop:** Demand → Intelligence → Opportunity → Alert → Lead → Sales → Revenue → Learning

---

## Technology Stack

| Layer       | Technology                    | Purpose                                         |
|-------------|-------------------------------|-------------------------------------------------|
| Backend     | Laravel 13.17 (PHP 8.3)       | REST API, queues, scheduler, business rules     |
| Frontend    | Angular (separate project)    | Dashboard, CRM, analytics, configuration        |
| Database    | PostgreSQL (SQLite for local) | Transactional and historical measurement data   |
| Cache/Queue | Redis + Laravel Horizon       | Jobs, caching, notifications                    |
| Auth        | Laravel Sanctum               | Token-based API authentication                  |
| AI          | OpenAI API / equivalent       | Intent, clustering, explanations, content       |
| Deployment  | Docker + Nginx                | Production environment                          |
| SMS         | Regional SMS provider         | Alerts                                          |
| Messaging   | WhatsApp Business Platform    | Lead capture                                    |

---

## Backend Directory Layout

```
Backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/   ← All API controllers
│   │   ├── Middleware/        ← EnsureSameTenant, CheckRole
│   │   └── Requests/          ← Form Requests (Auth/, Organization/, User/, …)
│   ├── Models/
│   │   ├── Concerns/
│   │   │   └── BelongsToOrganization.php   ← Multi-tenancy trait
│   │   └── [all Eloquent models]
│   ├── Services/
│   │   ├── Demand/            ← Trend detection, baseline computation
│   │   ├── Intelligence/      ← AI enrichment, intent, clustering
│   │   ├── Opportunities/     ← Scoring engine
│   │   ├── Leads/             ← Scoring, routing, qualification
│   │   ├── Alerts/            ← Rule evaluation, channel dispatch
│   │   ├── AI/                ← OpenAI integration wrapper
│   │   └── CRM/               ← Pipeline management helpers
│   ├── Jobs/
│   │   ├── CollectKeywordData.php    ← Queue: ingestion
│   │   ├── ProcessDemandSignal.php   ← Queue: processing
│   │   ├── CalculateOpportunity.php  ← Queue: scoring
│   │   ├── SendAlert.php             ← Queue: notifications
│   │   └── QualifyLead.php           ← Queue: lead_workflows
│   └── Notifications/
├── database/migrations/       ← Full schema (see below)
└── routes/api.php             ← All REST endpoints under /api/v1/
```

---

## Multi-Tenancy Rules

- Every tenant-owned table has an `organization_id` foreign key.
- The `BelongsToOrganization` trait auto-sets `organization_id` on create and adds `scopeForOrganization()` and `scopeForAuth()`.
- The `EnsureSameTenant` middleware is applied to all protected routes and verifies route model bindings belong to the authenticated user's organization.
- `CheckRole` middleware enforces RBAC: `->middleware('role:admin')`.
- **Never** query tenant-owned data without an `organization_id` constraint.

---

## Database Tables (Complete Schema)

| Table                  | Purpose                                       |
|------------------------|-----------------------------------------------|
| plans                  | Subscription tiers (Starter, Growth, etc.)    |
| organizations          | Tenants — each business is one organization   |
| subscriptions          | Org ↔ plan billing records                    |
| users                  | Team members with roles (owner→viewer)        |
| projects               | A business project with keywords & locations  |
| keywords               | Tracked search terms per project              |
| keyword_locations      | Geo targets for each keyword                  |
| keyword_measurements   | Daily interest/volume/growth data             |
| demand_clusters        | AI-grouped keyword clusters                   |
| cluster_keywords       | N:N pivot for clusters ↔ keywords             |
| opportunities          | Scored demand opportunities                   |
| landing_pages          | AI-generated + customer-controlled pages      |
| campaigns              | Marketing campaigns linked to opportunities   |
| leads                  | Captured prospects                            |
| lead_events            | Audit trail of lead lifecycle events          |
| contacts               | CRM contacts linked to leads                  |
| deals                  | CRM pipeline deals                            |
| tasks                  | Assigned sales tasks                          |
| notes                  | Lead/deal notes                               |
| alert_rules            | Threshold rules per org/project               |
| alerts                 | Fired alert records with delivery status      |
| data_sources           | Configured provider integrations              |
| usage_records          | Monthly metered usage per org                 |
| audit_logs             | Append-only security/config audit trail       |

---

## API Routes Summary (`/api/v1/`)

| Group         | Key Endpoints                                                    |
|---------------|------------------------------------------------------------------|
| Auth          | POST register, login, logout, forgot-password, reset-password; GET me |
| Organization  | GET/PATCH organization                                           |
| Users         | CRUD /users                                                      |
| Projects      | CRUD /projects                                                   |
| Keywords      | CRUD /keywords, bulk, /{id}/trend, /{id}/history                 |
| Trends        | GET /trends, /trends/rising                                      |
| Opportunities | GET/PATCH list+detail, POST /{id}/action, /{id}/dismiss          |
| Leads         | CRUD /leads, /{id}/qualify, assign, convert                      |
| CRM           | /deals, /tasks, /notes                                           |
| Alerts        | GET /alerts; CRUD /alert-rules                                   |
| Dashboard     | GET /dashboard, /dashboard/funnel, /dashboard/roi                |

---

## Opportunity Scoring Formula

```
score = growth_score * 0.30
      + intent_score * 0.25
      + geo_score    * 0.15
      + volume_score * 0.15
      + competition_score * 0.10
      + historical_score  * 0.05

0–39   LOW
40–59  MODERATE
60–79  HIGH
80–100 VERY HIGH
```

## Lead Scoring Formula

```
score = intent      * 0.30
      + engagement  * 0.20
      + location_fit* 0.15
      + product_fit * 0.15
      + budget_fit  * 0.10
      + recency     * 0.10

90–100 HOT  |  70–89 WARM  |  40–69 POTENTIAL  |  0–39 LOW
```

---

## Queue Architecture

| Queue name      | Jobs dispatched                            |
|-----------------|--------------------------------------------|
| ingestion       | CollectKeywordData                         |
| processing      | ProcessDemandSignal                        |
| scoring         | CalculateOpportunity                       |
| notifications   | SendAlert                                  |
| lead_workflows  | QualifyLead                                |
| reports         | (scheduled report generation — Sprint 12)  |

---

## Security Checklist

- All API routes protected by `auth:sanctum`
- Tenant isolation via `EnsureSameTenant` middleware
- RBAC roles: owner, admin, analyst, marketing, sales, viewer
- Integration credentials encrypted via `Crypt::encryptString()` in `DataSource`
- Audit trail in `AuditLog` for all config + sales actions
- Rate limiting on auth endpoints (to be added Sprint 2)
- `AuditLog::record()` static helper for easy logging anywhere

---

## MVP Sprint Roadmap

| Sprint | Deliverable                                   | Status         |
|--------|-----------------------------------------------|----------------|
| 1      | Architecture + Laravel + PostgreSQL + Redis   | ✅ Done        |
| 2      | Auth + multi-tenancy                          | ✅ Done        |
| 3      | Projects + keywords + locations               | 🔲 Next        |
| 4      | First permitted provider (Google Trends API)  | 🔲 Planned     |
| 5      | Historical pipeline (7/30/90-day baselines)   | 🔲 Planned     |
| 6      | Trend engine (rising/spike/declining states)  | 🔲 Planned     |
| 7      | Opportunity engine (explainable 0–100 scores) | 🔲 Planned     |
| 8      | Dashboard (trends/opportunities visible)      | 🔲 Planned     |
| 9      | SMS/email alerts                              | 🔲 Planned     |
| 10     | Landing pages + lead capture                  | 🔲 Planned     |
| 11     | Basic CRM + lead scoring                      | 🔲 Planned     |
| 12     | Security + testing + production deployment    | 🔲 Planned     |

---

## Product Boundaries (Do Not Cross)

- ✅ Aggregated trend signals, public/permitted data, customer-authorized analytics
- ❌ Individual private search histories
- ❌ Identity of anonymous searchers
- ❌ Secret surveillance of individuals
- ❌ Automatic advertising spend (recommend only)
- ❌ Scraping that violates provider terms
