# Waste4Change - Waste Management Reporting System

## Quick Start

```bash
docker compose up -d
docker compose exec api php artisan migrate --force
docker compose exec api php artisan db:seed --force
```

Then open:
- **Web**: http://localhost:3000
- **API**: http://localhost:8000
- **Swagger Docs**: http://localhost:8000/api/documentation

## Seeding 1 Million Schedules

```bash
docker compose exec api php artisan db:seed --class=DatabaseSeeder --seed-schedules=1000000 --force
```

## Stop Everything

```bash
docker compose down
```

## Project Structure

```
apps/
  api/     # Laravel 13 + PostgreSQL
  web/     # Nuxt 4 SPA + TailwindCSS
```

## Technical Decisions

### Backend
- **Database caching** — All heavy aggregation queries cached in `cache` table. TTL: 5 min per filter combination. Run `docker compose exec api php artisan cache:clear` to reset.
- **Performance indexes** — Strategic indexes on `schedules`, `collects`, `sort_items` for fast dashboard queries.
- **Chunked seeding** — Schedules inserted in 1,000-row batches to handle 1M+ rows.

### Frontend
- **In-memory client cache** — `useApi()` caches responses with TTL + deduplicates in-flight requests.
- **Debounced filters** — 300-400ms delay prevents API spam.
- **Skeleton loaders + error states** — Loading states with retry buttons.

## Requirements Checklist

| Requirement | Status |
|-------------|--------|
| Laravel + PostgreSQL | ✅ |
| Swagger OpenAPI | ✅ |
| 8 DB tables with relations | ✅ |
| Performance indexes | ✅ |
| Database caching | ✅ |
| 1M schedule seeding | ✅ |
| GET /api/schedules + /{id} | ✅ |
| GET /api/dashboard (7 sections) | ✅ |
| Dashboard filters | ✅ |
| Nuxt 4 + TailwindCSS | ✅ |
| Chart visualization | ✅ |
| Responsive UI | ✅ |
