# Data Source & Integration Architecture

## Overview

The system now supports two types of data collection:

1. **DataSource Integrations** - API-based providers (Google Trends, Google Ads, Search Console, SMS, Webhooks)
2. **SourceScrapers** - Active data collection from websites, RSS feeds, tenders, etc.

---

## Architecture Flow

```
┌─────────────────────────────────────────────────────────┐
│                   DATA SOURCES                          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  API INTEGRATIONS          │    WEB SCRAPERS           │
│  ─────────────────────────────────────────────────     │
│  • Google Trends           │    • RSS Feeds             │
│  • Google Ads              │    • Tender Portals        │
│  • Search Console          │    • Websites              │
│  • Africa's Talking        │    • Business Directories  │
│  • Webhooks                │    • News Sites            │
│                                                          │
└─────────────────┬────────────────────────────┬──────────┘
                  │                            │
                  ▼                            ▼
        ┌─────────────────┐         ┌──────────────────┐
        │  DataProvider   │         │  ScraperService  │
        │    Factory      │         │                  │
        └────────┬────────┘         └────────┬─────────┘
                 │                           │
                 │                           ▼
                 │                  ┌──────────────────┐
                 │                  │  ParserService   │
                 │                  └────────┬─────────┘
                 │                           │
                 │                           ▼
                 │                  ┌──────────────────┐
                 │                  │ NormalizerService│
                 │                  └────────┬─────────┘
                 │                           │
                 └───────────┬───────────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │ ScrapedItem      │
                    │ (Normalized Data)│
                    └────────┬─────────┘
                             │
                ┌────────────┼────────────┐
                ▼            ▼            ▼
         ┌──────────┐ ┌──────────┐ ┌──────────┐
         │ Keyword  │ │Opportunity│ │  Lead    │
         │ Matching │ │  Scoring  │ │ Detection│
         └────┬─────┘ └─────┬────┘ └────┬─────┘
              │             │            │
              └─────────────┼────────────┘
                            │
                            ▼
                    ┌──────────────┐
                    │ CRM Pipeline │
                    └──────────────┘
```

---

## 1. DataSource Integrations (API-based)

### Models
- **DataSource** - Stores integration configuration
  - `organization_id`
  - `name`, `type`, `status`
  - `encrypted_credentials`
  - `last_sync_at`, `sync_meta`

### Services
- **DataProviderFactory** - Creates provider instances
  - `GoogleTrendsProvider`
  - `GoogleAdsProvider`
  - `SearchConsoleProvider`
  - `AfricasTalkingProvider`
  - `WebhookProvider`

### Controllers
- **IntegrationController** (Web) - Manages integration UI
  - CRUD operations
  - Test connections
  - Pause/activate
- **IntegrationController** (API) - REST API for integrations

### Views
- `integrations/index.blade.php` - List all integrations
- `integrations/create.blade.php` - Create new integration
- `integrations/show.blade.php` - Integration details
- `integrations/edit.blade.php` - Edit integration

---

## 2. SourceScrapers (Active Collection)

### Models
- **SourceScraper** - Configuration for data source
- **ScrapeJob** - Individual scrape execution
- **ScrapedItem** - Collected and normalized data
- **SourceEvent** - Audit trail

### Services

#### SourceManager
Orchestrates the entire source lifecycle:
```php
- createSource() - Set up new source
- updateSource() - Modify configuration
- testSource() - Verify connection
- runSource() - Execute scrape
- activateSource() / pauseSource()
- getSourceStats() - Performance metrics
```

#### ScraperService
Executes data collection:
```php
- scrape() - Main entry point
- scrapeRss() - RSS feeds
- scrapeApi() - API endpoints
- scrapeTender() - Tender portals
- scrapeWebsite() - Generic websites
```

#### ParserService
Extracts structured data from HTML:
```php
- parseTenders() - Tender-specific parsing
- parseWebsite() - Generic HTML parsing
```

#### NormalizerService
Enriches and scores data:
```php
- normalize() - Main normalization
- detectIntent() - transactional/commercial/informational
- calculateRelevanceScore() - Keyword matching (0-100)
- calculateLeadScore() - Lead potential (0-100)
- calculateOpportunityScore() - Business value (0-100)
- extractEntities() - Emails, phones, amounts, locations
```

### Jobs
- **RunSourceScrape** - Queue job to execute scraping
  - Queue: `ingestion`
  - Timeout: 5 minutes
  - Retries: 3

---

## 3. Scoring & Intelligence

### Intent Detection
- **tender** - RFP, tender, bid keywords
- **transactional** - Buy, purchase, looking for
- **commercial** - Price, supplier, vendor
- **informational** - Everything else

### Lead Score (0-100)
```
Intent:         35 points (tender) | 30 (transactional) | 20 (commercial)
Contact info:   25 points (if email/phone present)
Requirements:   20 points (if price/deadline mentioned)
Source bonus:   20 points (tender/lead_capture sources)
```

### Opportunity Score (0-100)
```
Intent:         40 points (tender) | 30 (transactional) | 25 (commercial)
Relevance:      30% of keyword relevance score
Category:       15 points (tender sources)
Recency:        15 points (newer items)
```

---

## 4. Data Flow Example

### Example: Tender Portal Scraping

1. **Create Source**
```php
$source = SourceManager::createSource($organization, [
    'name' => 'Kenya Government Tenders',
    'type' => 'tender',
    'base_url' => 'https://tenders.go.ke/website/tenders',
    'schedule' => '0 */6 * * *', // Every 6 hours
    'configuration' => [
        'item_selector' => '.tender-item',
        'title_selector' => 'h3.title',
        'description_selector' => '.description',
        'url_selector' => 'a.details',
        'date_selector' => '.closing-date',
    ],
]);
```

2. **Automatic Scraping**
```
Cron → RunSourceScrape Job → ScraperService::scrape()
  ↓
Fetch HTML → ParserService::parseTenders()
  ↓
Extract Items → foreach item...
  ↓
NormalizerService::normalize()
  ↓
Calculate Scores (relevance, lead, opportunity)
  ↓
Extract Entities (emails, phones, locations)
  ↓
Detect Intent (tender, transactional, etc.)
  ↓
Create ScrapedItem (status: pending)
```

3. **Post-Processing** (Future)
```
ProcessScrapedItem Job (queue: processing)
  ↓
IF opportunity_score >= 70
  → Create Opportunity
  → Trigger Alert
  
IF lead_score >= 70 AND has_contact_info
  → Create Lead
  → Assign to Sales
```

---

## 5. Database Tables

### DataSource Integrations
```sql
data_sources
- id, organization_id, name, type, status
- encrypted_credentials, last_sync_at, sync_meta
```

### Source Scrapers
```sql
source_scrapers
- id, organization_id, name, type, category
- base_url, configuration, credentials, schedule
- status, last_run_at, next_run_at, error_count

scrape_jobs
- id, source_scraper_id, status
- started_at, completed_at
- items_found, items_new, items_updated, items_failed

scraped_items
- id, source_scraper_id, scrape_job_id
- content_hash (SHA-256 for deduplication)
- title, description, content, url
- intent, relevance_score, lead_score, opportunity_score
- processing_status, matched_keywords, extracted_entities

source_events
- id, source_scraper_id, event_type, severity
- message, metadata (audit trail)
```

---

## 6. Usage Examples

### Create an Integration (Google Trends)
```php
// Via Web UI
/integrations/create?type=google_trends

// No credentials needed - public data
```

### Create a Scraper (RSS Feed)
```php
POST /sources
{
    "name": "TechCrunch Africa",
    "type": "rss",
    "base_url": "https://techcrunch.com/category/africa/feed/",
    "schedule": "0 */2 * * *"
}
```

### Test Connection
```php
POST /sources/{id}/test

// Returns:
{
    "success": true,
    "message": "RSS feed is valid. Found 25 items."
}
```

### Run Manually
```php
POST /sources/{id}/run

// Dispatches RunSourceScrape job to queue
```

### View Stats
```php
GET /sources/{id}

// Shows:
- Total jobs run
- Success rate
- Items collected
- Leads/opportunities generated
```

---

## 7. Configuration Examples

### Tender Portal Configuration
```json
{
    "item_selector": ".tender-listing",
    "title_selector": "h2.tender-title",
    "description_selector": ".tender-description",
    "url_selector": "a.view-details",
    "date_selector": ".closing-date",
    "id_selector": ".tender-ref"
}
```

### API Configuration
```json
{
    "items_path": "data.results",
    "field_mapping": {
        "title": "headline",
        "description": "summary",
        "url": "link",
        "published_at": "date"
    }
}
```

---

## 8. Future Enhancements

1. **Browser Automation** - Playwright integration for JavaScript-heavy sites
2. **AI Entity Extraction** - Use OpenAI to extract companies, contacts, requirements
3. **Smart Scheduling** - Adjust frequency based on data freshness
4. **Duplicate Detection** - Advanced fuzzy matching beyond content hash
5. **Auto-Configuration** - AI-powered selector generation
6. **Multi-page Crawling** - Follow pagination automatically
7. **Rate Limiting** - Respect robots.txt and throttle requests
8. **Proxy Support** - Rotate IPs for high-volume scraping

---

## 9. Security & Compliance

✅ **Implemented:**
- Encrypted credentials (Laravel Crypt)
- Multi-tenant isolation
- Audit logging (SourceEvent)
- Content hash deduplication

⚠️ **Required:**
- robots.txt respect
- Rate limiting per source
- User-agent identification
- Data retention policies
- Terms of service compliance checks

---

## 10. Monitoring & Alerts

### Health Metrics
- Success rate per source
- Average scrape duration
- Error threshold (5 failures = pause)
- Items per day
- Duplicate rate
- Conversion rate (item → lead)

### Event Types
- `source_created`, `source_activated`, `source_paused`
- `job_started`, `job_completed`, `job_failed`
- `error_threshold_exceeded`
- `configuration_changed`, `credentials_updated`

---

## Summary

The system now provides a complete **Demand Intelligence & Lead Discovery Platform**:

1. **API Integrations** for Google Trends, Ads, Search Console, SMS alerts
2. **Active Scrapers** for RSS, tenders, websites, directories
3. **Intelligent Normalization** with intent detection and scoring
4. **Entity Extraction** for contacts, amounts, locations
5. **Deduplication** via content hashing
6. **Queue-based Processing** for scalability
7. **Full Audit Trail** for compliance
8. **Multi-tenant Isolation** for SaaS deployment

Next steps: Wire up the ProcessScrapedItem job to automatically convert high-scoring items into Opportunities and Leads.
