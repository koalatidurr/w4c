# Waste4Change API

Laravel 13 + PostgreSQL backend for the Waste Management Reporting System.

## Setup

```bash
# Start all services
docker compose up -d postgres api

# Run migrations + indexes
docker compose exec api php artisan migrate --force

# Seed test data
docker compose exec api php artisan db:seed --force

# Swagger docs
open http://localhost:8000/api/documentation
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/schedules` | List schedules (paginated, filter: `date_from`, `date_to`) |
| GET | `/api/schedules/{id}` | Schedule detail with collect + sort |
| GET | `/api/dashboard` | Full dashboard (7 sections), filter: `date_from`, `date_to`, `group_by` (day/month/year) |
| GET | `/api/dashboard/waste-weight` | Waste weight chart data |
| GET | `/api/dashboard/transport` | Transport summary (DONE/SKIP/Belum) |
| GET | `/api/dashboard/sorting` | Sorting status (sorted/unsorted) |
| GET | `/api/dashboard/top-wastes` | Top 5 heaviest wastes |
| GET | `/api/dashboard/trend` | Schedule vs realization trend |

## Dashboard Caching

All dashboard endpoints are cached in the `cache` database table. Cache key is based on all filter parameters (date_from, date_to, group_by, status, etc.). Default TTL is 5 minutes.

To clear the cache:
```bash
docker compose exec api php artisan cache:clear
```

## Seeding 1 Million Schedules

```bash
docker compose exec api php artisan db:seed --class=DatabaseSeeder --seed-schedules=1000000 --force
```

Seeds 10 trashbags, 100 wastes, ~500 clients, then bulk-inserts schedules (~550/day across 4 years back + 1 year forward). Collects generated for past schedules only (80% DONE / 20% SKIP). Sorts created only for DONE collects.

## Tech Decisions

- **DashboardService** (`app/Services/`): All aggregation queries decoupled from controllers. Uses raw SQL with `to_char()` for PostgreSQL date grouping.
- **QueryFilter** (`app/Helpers/`): Parses common filter params (date_from, date_to, group_by, etc.) keeping controllers DRY.
- **ApiResponse** (`app/Helpers/`): Consistent JSON wrapper (`{success, data, meta}`).
- **Chunked seeding**: Schedules inserted in 1,000-row batches to handle 1M+ rows.
- **Database caching**: Uses Laravel's `Cache::remember()` with database driver — no Redis needed.
- **Performance indexes**: Strategic indexes on `schedules`, `collects`, `sort_items` for fast aggregations.
- **OpenAPI spec**: `openapi.yaml` at project root, Swagger UI at `/api/documentation`.
