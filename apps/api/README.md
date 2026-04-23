# Waste4Change API

Laravel 13 + PostgreSQL backend for the Waste Management Reporting System.

## Setup

```bash
# Start all services
docker compose up -d

# Run migrations
docker compose exec api php artisan migrate --force

# Seed test data (~3K schedules, ~1.5K collects)
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

## Seeding 1 Million Schedules

```bash
# Run seeder with 1M target
docker compose exec api php artisan db:seed --class=DatabaseSeeder --seed-schedules=1000000 --force
```

Seeds 10 trashbags, 100 wastes, 50 clients, then bulk-inserts schedules (~550/day across 4 years back + 1 year forward). Collects generated for past schedules only (80% DONE / 20% SKIP). Sorts created only for DONE collects.

## Tech Decisions

- **DashboardService** (`app/Services/`): All aggregation queries decoupled from controllers. Uses raw SQL with `to_char()` for PostgreSQL date grouping.
- **QueryFilter** (`app/Helpers/`): Parses common filter params (date_from, date_to, group_by, etc.) keeping controllers DRY.
- **ApiResponse** (`app/Helpers/`): Consistent JSON wrapper (`{success, data, meta}`).
- **Chunked seeding**: Schedules inserted in 1,000-row batches to handle 1M+ rows.
- **OpenAPI spec**: `openapi.yaml` at project root, Swagger UI at `/api/documentation`.
