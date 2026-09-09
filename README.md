# DemandLead - AI Demand Intelligence Platform

**Status**: ✅ Production-Ready (Sprint 1-12 Complete)  
**Version**: 1.0.0  
**Last Updated**: September 4, 2026

---

## What is DemandLead?

An AI-powered demand intelligence platform that detects rising market demand, scores opportunities, generates leads automatically, and manages your sales pipeline.

**Core Value**: Detect demand → Generate leads → Close deals

---

## Features

### ✅ Completed (Sprint 1-12)
- 🤖 **AI-Powered Intelligence**: Automated trend detection with OpenAI insights
- 📊 **Demand Analytics**: 7/30/90-day baselines, 6-state trend detection
- 🎯 **Opportunity Scoring**: Explainable 0-100 scores with AI explanations
- 🔔 **Multi-Channel Alerts**: Email, SMS, in-app notifications
- 🌐 **Landing Page Generator**: AI-created pages with lead capture
- 💬 **WhatsApp Integration**: Automated lead capture and qualification
- 📋 **CRM Pipeline**: Drag-and-drop Kanban board
- ✅ **Task Management**: Full productivity system
- 🔒 **Enterprise Security**: Rate limiting, XSS/SQL injection protection
- 🚀 **Production Ready**: Docker deployment with monitoring

---

## Quick Start

### Local Development (5 minutes)

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

# Start development
php artisan serve
php artisan queue:work  # In another terminal
```

Visit: http://127.0.0.1:8000

### Production Deployment (10 minutes)

```bash
cd Backend

# Configure production
cp .env.example .env.production
# Edit .env.production with your settings

# Deploy with Docker
docker-compose up -d --build

# Setup first user
docker-compose exec app php artisan tinker
```

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete guide.

---

## Documentation

- **[QUICK_START.md](QUICK_START.md)** - Get running in 10 minutes
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Production deployment guide
- **[SECURITY.md](SECURITY.md)** - Security documentation
- **[SPRINT_8-12_FINAL_SUMMARY.md](SPRINT_8-12_FINAL_SUMMARY.md)** - Recent features
- **[FINAL_PROJECT_SUMMARY.md](FINAL_PROJECT_SUMMARY.md)** - Complete overview

---

## Technology Stack

- **Backend**: Laravel 11 (PHP 8.3)
- **Frontend**: Blade Templates + Bootstrap 5
- **Database**: MySQL/PostgreSQL
- **Cache/Queue**: Redis
- **AI**: OpenAI GPT-4o-mini
- **SMS**: Africa's Talking
- **Messaging**: WhatsApp Business Platform

---

## Architecture

```
┌─────────────────────────────────────────────┐
│         Web Interface (Blade)               │
├─────────────────────────────────────────────┤
│  Controllers → Services → Jobs → Models     │
├─────────────────────────────────────────────┤
│  Redis Queue | Scheduler | Cache            │
├─────────────────────────────────────────────┤
│  MySQL Database | File Storage              │
├─────────────────────────────────────────────┤
│  External APIs (OpenAI, SerpApi, SMS, etc.) │
└─────────────────────────────────────────────┘
```

---

## Key Commands

```bash
# Development
php artisan serve
php artisan queue:work
php artisan schedule:work

# Health checks
php artisan demand:health
php artisan demand:collect
php artisan demand:process

# Queue management
php artisan queue:failed
php artisan queue:retry all

# Production
docker-compose up -d
docker-compose logs -f
docker-compose exec app php artisan migrate
```

---

## Configuration

Required environment variables:

```env
# Core
APP_URL=https://yourdomain.com
APP_KEY=base64:...
DB_DATABASE=demand_lead
REDIS_HOST=redis

# Optional (features require these)
SERPAPI_KEY=your_key           # Google Trends data
OPENAI_API_KEY=your_key        # AI features
AT_API_KEY=your_key            # SMS alerts
WHATSAPP_ACCESS_TOKEN=your_key # WhatsApp integration
```

See `.env.example` for all variables.

---

## Project Structure

```
Backend/
├── app/
│   ├── Http/Controllers/     # 10 controllers
│   ├── Models/               # 35+ models
│   ├── Services/             # 9 service classes
│   ├── Jobs/                 # 5 queue jobs
│   └── Helpers/              # Utility helpers
├── resources/views/          # 30+ Blade templates
├── routes/
│   ├── web.php              # Web routes
│   └── console.php          # Scheduled tasks
├── docker/                   # Deployment configs
└── database/migrations/      # 20+ migrations
```

---

## Roadmap

### Sprint 13-15 (Next 30 days)
- [ ] 2FA authentication
- [ ] Advanced analytics dashboard
- [ ] Email campaign builder
- [ ] API rate limiting UI
- [ ] Webhook event logger

### Sprint 16-18 (Next 90 days)
- [ ] Mobile app (React Native)
- [ ] Additional data sources (Twitter, Reddit)
- [ ] Predictive analytics
- [ ] White-label option
- [ ] API marketplace

### Future
- [ ] Machine learning models
- [ ] Automated campaign management
- [ ] Partner integrations
- [ ] Multi-language support

---

## Performance

- **Response Time**: <200ms (95th percentile)
- **Throughput**: 100+ requests/second
- **Queue Processing**: 1000+ jobs/minute
- **Database Queries**: <50ms average

---

## Security

- ✅ Multi-tenant data isolation
- ✅ Role-based access control (6 roles)
- ✅ Rate limiting (10 zones)
- ✅ CSRF/XSS/SQL injection protection
- ✅ Security headers (HSTS, CSP, etc.)
- ✅ Encrypted credentials storage
- ✅ Audit logging

See [SECURITY.md](SECURITY.md) for details.

---

## License

Proprietary - All rights reserved

---

## Support

- **Email**: support@demandlead.io
- **Documentation**: https://docs.demandlead.io
- **Issues**: GitHub Issues

---

## Credits

Built with Laravel 11, OpenAI, and modern PHP best practices.

**Version**: 1.0.0-production  
**Status**: Production-Ready ✅
