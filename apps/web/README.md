# Waste4Change Web

Nuxt 3 SPA + TailwindCSS frontend consuming the Laravel REST API.

## Setup

```bash
# Build & start all services
docker compose up -d --build

# Local dev (requires Node 20+)
cd apps/web
npm install
npm run dev
```

## Pages

- `/` — Schedule list with pagination + date filter
- `/schedules/{id}` — Schedule detail (collect items, sort items)
- `/dashboard` — Full dashboard with charts + filter controls

## Tech Decisions

- **Nuxt SPA (SSR disabled)** — `ssr: false` in `nuxt.config.ts`. All rendering is client-side, no hydration issues.
- **`useApi()` composable** (`app/composables/useApi.ts`) — Thin wrapper around Nuxt's `$fetch` with typed `get<T>()` method. Returns data directly, no plugin needed.
- **Reactive state** — Pages use `ref()`/`reactive()` for `loading`, `errorMsg`, and `data`. Filter changes trigger refetch via `watch()`.
- **ApexCharts** via `vue3-apexcharts`, wrapped in `<ClientOnly>` for SSR safety.
- **Shadcn-style components** (`app/components/ui/`) — Hand-crafted UI components (Card, Table, Badge, Button, Input, etc.).
- API base URL configurable via `NUXT_PUBLIC_API_BASE` env var.
