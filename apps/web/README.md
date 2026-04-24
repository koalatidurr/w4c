# Waste4Change Web

Nuxt 4 SPA + TailwindCSS frontend consuming the Laravel REST API.

## Setup

```bash
cd apps/web
bun install
bun run dev
```

The API must be running at `http://localhost:8000/api`. For Docker setup:

```bash
cd ../..
docker compose up -d postgres api
bun run dev
```

## Production Build

```bash
bun run build
node .output/server/index.mjs
```

## Pages

- `/` — Schedule list with pagination + date filter
- `/schedules/{id}` — Schedule detail (collect items, sort items)
- `/dashboard` — Full dashboard with charts + filter controls

## API Base URL

Configure via environment variable:
```bash
NUXT_PUBLIC_API_BASE=http://localhost:8000/api bun run dev
```

## Tech Decisions

- **SPA mode** — No SSR, all rendering is client-side for fast API-driven rendering.
- **`useApi()` composable** (`app/composables/useApi.ts`): Thin fetch wrapper with in-memory caching, TTL support, and request deduplication.
- **Debounced filters** — 300-400ms delay prevents API spam on filter changes.
- **Skeleton loaders + error states** — Loading states with retry buttons for better UX.
- **ApexCharts** via `vue3-apexcharts`, wrapped in `<ClientOnly>` for SSR safety.
- **Custom UI components** (`app/components/`): Hand-crafted components (Badge, Button, Card, Table, Input, Skeleton, etc.) — no external UI library dependencies.
- **Design tokens**: CSS variables in `app/assets/css/main.css` for consistent theming.
