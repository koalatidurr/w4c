<?php

namespace App\Services;

use App\Helpers\QueryFilter;
use App\Models\Collect;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Handles all dashboard aggregation queries.
 * Decoupled from controllers for reusability and testability.
 * All methods are cached using Redis with filter-based keys.
 */
class DashboardService
{
    protected QueryFilter $filters;

    /** Cache TTL in seconds: 5 minutes for filtered data, 60s for recent */
    private const CACHE_TTL = 300;

    public function __construct(QueryFilter $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Generate a unique cache key from current filter parameters.
     */
    protected function cacheKey(string $method): string
    {
        $params = $this->filters->getAll();
        ksort($params);
        $hash = md5(json_encode($params));
        return "dashboard:{$method}:{$hash}";
    }

    /**
     * Cache-aware query wrapper.
     */
    protected function cached(string $method, callable $callback): array
    {
        return Cache::remember(
            $this->cacheKey($method),
            self::CACHE_TTL,
            $callback
        );
    }

    // ─────────────────────────────────────────────────────────────
    // 1. Chart berat material terpilah
    // ─────────────────────────────────────────────────────────────
    public function wasteWeightChart(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $groupBy = $this->filters->groupBy();
            $dateFrom = $this->filters->dateFrom();
            $dateTo = $this->filters->dateTo();

            $periodSql = match ($groupBy) {
                'month' => "to_char(sc.date, 'YYYY-MM')",
                'year' => "to_char(sc.date, 'YYYY')",
                default => "to_char(sc.date, 'YYYY-MM-DD')",
            };

            $results = DB::table('sort_items as si')
                ->join('sorts as s', 'si.sort_id', '=', 's.id')
                ->join('collects as c', 's.collect_id', '=', 'c.id')
                ->join('schedules as sc', 'c.schedule_id', '=', 'sc.id')
                ->join('wastes as w', 'si.waste_id', '=', 'w.id')
                ->when($dateFrom, fn($q) => $q->whereDate('sc.date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('sc.date', '<=', $dateTo))
                ->select(
                    DB::raw("{$periodSql} as period"),
                    'w.id as waste_id',
                    'w.name as waste_name',
                    DB::raw('SUM(si.weight) as total_weight')
                )
                ->groupBy(DB::raw($periodSql), 'w.id', 'w.name')
                ->orderBy('period')
                ->orderBy('waste_name')
                ->get();

            $periods = $results->pluck('period')->unique()->sort()->values()->toArray();
            $wasteMap = $results->groupBy('waste_name');

            $datasets = [];
            foreach ($wasteMap as $wasteName => $items) {
                $itemMap = $items->keyBy('period');
                $datasets[] = [
                    'waste' => $wasteName,
                    'data' => array_map(fn($p) => (float) ($itemMap[$p]->total_weight ?? 0), $periods),
                ];
            }

            return [
                'periods' => $periods,
                'datasets' => $datasets,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────
    // 2. Total pengangkutan
    // ─────────────────────────────────────────────────────────────
    public function transportSummary(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $dateFrom = $this->filters->dateFrom();
            $dateTo = $this->filters->dateTo();

            $query = DB::table('collects as c')
                ->join('schedules as s', 'c.schedule_id', '=', 's.id')
                ->when($dateFrom, fn($q) => $q->whereDate('s.date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('s.date', '<=', $dateTo));

            $done = (clone $query)->where('c.status', 'DONE')->count();
            $skip = (clone $query)->where('c.status', 'SKIP')->count();

            $totalSchedules = Schedule::query()
                ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
                ->count();

            return [
                'done' => $done,
                'skip' => $skip,
                'not_collected' => $totalSchedules - $done - $skip,
                'total' => $totalSchedules,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────
    // 3. Status pemilahan
    // ─────────────────────────────────────────────────────────────
    public function sortingStatus(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $dateFrom = $this->filters->dateFrom();
            $dateTo = $this->filters->dateTo();

            $query = Collect::query()
                ->where('status', 'DONE')
                ->whereHas('schedule', fn($q) => $q
                    ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
                    ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
                );

            $total = $query->count();
            $sorted = (clone $query)->whereHas('sort')->count();

            return [
                'sorted' => $sorted,
                'unsorted' => $total - $sorted,
                'total' => $total,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Rata-rata berat per material per periode
    // ─────────────────────────────────────────────────────────────
    public function averageWeightByWaste(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $dateFrom = $this->filters->dateFrom();
            $dateTo = $this->filters->dateTo();
            $groupBy = $this->filters->groupBy();

            $periodSql = match ($groupBy) {
                'month' => "to_char(sc.date, 'YYYY-MM')",
                'year' => "to_char(sc.date, 'YYYY')",
                default => "to_char(sc.date, 'YYYY-MM-DD')",
            };

            return DB::table('sort_items as si')
                ->join('sorts as s', 'si.sort_id', '=', 's.id')
                ->join('collects as c', 's.collect_id', '=', 'c.id')
                ->join('schedules as sc', 'c.schedule_id', '=', 'sc.id')
                ->join('wastes as w', 'si.waste_id', '=', 'w.id')
                ->when($dateFrom, fn($q) => $q->whereDate('sc.date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('sc.date', '<=', $dateTo))
                ->select(
                    DB::raw("{$periodSql} as period"),
                    'w.id as waste_id',
                    'w.name as waste_name',
                    DB::raw('ROUND(AVG(si.weight), 2) as avg_weight'),
                    DB::raw('COUNT(*) as item_count')
                )
                ->groupBy(DB::raw($periodSql), 'w.id', 'w.name')
                ->orderBy('period')
                ->get()
                ->groupBy('period')
                ->map(fn($items) => $items->map(fn($i) => [
                    'waste_id' => $i->waste_id,
                    'waste_name' => $i->waste_name,
                    'avg_weight' => (float) $i->avg_weight,
                    'item_count' => (int) $i->item_count,
                ])->values())
                ->toArray();
        });
    }

    // ─────────────────────────────────────────────────────────────
    // 5. Top 5 material terberat
    // ─────────────────────────────────────────────────────────────
    public function top5HeaviestWastes(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $dateFrom = $this->filters->dateFrom();
            $dateTo = $this->filters->dateTo();

            return DB::table('sort_items as si')
                ->join('sorts as s', 'si.sort_id', '=', 's.id')
                ->join('collects as c', 's.collect_id', '=', 'c.id')
                ->join('schedules as sc', 'c.schedule_id', '=', 'sc.id')
                ->join('wastes as w', 'si.waste_id', '=', 'w.id')
                ->when($dateFrom, fn($q) => $q->whereDate('sc.date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('sc.date', '<=', $dateTo))
                ->select(
                    'w.id as waste_id',
                    'w.name as waste_name',
                    DB::raw('SUM(si.weight) as total_weight'),
                    DB::raw('ROUND(AVG(si.weight), 2) as avg_weight'),
                    DB::raw('COUNT(DISTINCT si.id) as item_count')
                )
                ->groupBy('w.id', 'w.name')
                ->orderByDesc('total_weight')
                ->limit(5)
                ->get()
                ->map(fn($w) => [
                    'waste_id' => $w->waste_id,
                    'waste_name' => $w->waste_name,
                    'total_weight' => round((float) $w->total_weight, 2),
                    'avg_weight' => (float) $w->avg_weight,
                    'item_count' => (int) $w->item_count,
                ])
                ->toArray();
        });
    }

    // ─────────────────────────────────────────────────────────────
    // 6. Persentase DONE vs SKIP
    // ─────────────────────────────────────────────────────────────
    public function doneSkipPercentage(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $dateFrom = $this->filters->dateFrom();
            $dateTo = $this->filters->dateTo();

            $total = Collect::query()
                ->whereHas('schedule', fn($q) => $q
                    ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
                    ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
                )
                ->count();

            if ($total === 0) {
                return ['done_pct' => 0, 'skip_pct' => 0, 'total' => 0];
            }

            $done = Collect::query()
                ->where('status', 'DONE')
                ->whereHas('schedule', fn($q) => $q
                    ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
                    ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
                )
                ->count();

            $skip = $total - $done;

            return [
                'done_pct' => round($done / $total * 100, 2),
                'skip_pct' => round($skip / $total * 100, 2),
                'total' => $total,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────
    // 7. Tren jadwal vs realisasi
    // ─────────────────────────────────────────────────────────────
    public function scheduleVsRealizationTrend(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $dateFrom = $this->filters->dateFrom();
            $dateTo = $this->filters->dateTo();
            $groupBy = $this->filters->groupBy();

            $periodSql = match ($groupBy) {
                'month' => "to_char(sc.date, 'YYYY-MM')",
                'year' => "to_char(sc.date, 'YYYY')",
                default => "to_char(sc.date, 'YYYY-MM-DD')",
            };

            return DB::table('schedules as sc')
                ->leftJoin('collects as c', 'sc.id', '=', 'c.schedule_id')
                ->when($dateFrom, fn($q) => $q->whereDate('sc.date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('sc.date', '<=', $dateTo))
                ->select(
                    DB::raw("{$periodSql} as period"),
                    DB::raw('COUNT(DISTINCT sc.id) as scheduled'),
                    DB::raw('COUNT(DISTINCT c.id) as collected')
                )
                ->groupBy(DB::raw($periodSql))
                ->orderBy('period')
                ->get()
                ->map(fn($row) => [
                    'period' => $row->period,
                    'scheduled' => (int) $row->scheduled,
                    'collected' => (int) $row->collected,
                    'collected_pct' => $row->scheduled > 0
                        ? round($row->collected / $row->scheduled * 100, 2)
                        : 0,
                ])
                ->toArray();
        });
    }

    // ─────────────────────────────────────────────────────────────
    // All-in-one dashboard
    // ─────────────────────────────────────────────────────────────
    public function dashboard(): array
    {
        return [
            'waste_weight_chart' => $this->wasteWeightChart(),
            'transport_summary' => $this->transportSummary(),
            'sorting_status' => $this->sortingStatus(),
            'average_weight_by_waste' => $this->averageWeightByWaste(),
            'top5_heaviest_wastes' => $this->top5HeaviestWastes(),
            'done_skip_percentage' => $this->doneSkipPercentage(),
            'schedule_realization_trend' => $this->scheduleVsRealizationTrend(),
        ];
    }
}
