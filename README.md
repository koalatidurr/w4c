# Waste4Change - Waste Management Reporting System

Monorepo containing Laravel API (`apps/api/`) and Nuxt frontend (`apps/web/`).

## Quick Start

```bash
docker compose up -d

# Setup DB
docker compose exec api php artisan migrate --force
docker compose exec api php artisan db:seed --force

# Access
# API:  http://localhost:8000
# Web:  http://localhost:3000
# Docs: http://localhost:8000/api/documentation
```

## Seeding 1 Million Schedules

```bash
docker compose exec api php artisan db:seed --class=DatabaseSeeder --seed-schedules=1000000 --force
```

## Project Structure

```
apps/
  api/          # Laravel 13 + PostgreSQL
    app/
      Helpers/       # QueryFilter, ApiResponse
      Http/
        Controllers/  # ScheduleController, DashboardController
        Resources/    # API Resource classes
      Models/         # Eloquent models
      Services/       # DashboardService (aggregation logic)
    database/
      factories/      # Model factories
      migrations/     # DB schema
      seeders/       # DatabaseSeeder (chunked bulk insert)
    openapi.yaml     # Swagger OpenAPI spec
  web/           # Nuxt 3 + TailwindCSS
    app/
      composables/    # useApi (TanStack Query hooks)
      components/     # Shadcn-style UI components
      pages/          # Schedule list, detail, dashboard
      plugins/        # TanStack Query setup
```

## Requirements Checklist

| Requirement | Status |
|-------------|--------|
| Laravel + PostgreSQL | ✅ |
| Swagger OpenAPI (openapi.yaml) | ✅ |
| 8 DB tables with relations | ✅ |
| 1M schedule seeding | ✅ |
| GET /api/schedules + /{id} | ✅ |
| GET /api/dashboard (7 sections) | ✅ |
| Dashboard filters (date, group_by) | ✅ |
| Nuxt 3 + TailwindCSS | ✅ |
| TanStack Query | ✅ |
| Chart visualization | ✅ |
| Responsive UI | ✅ |
| README with setup instructions | ✅ |
