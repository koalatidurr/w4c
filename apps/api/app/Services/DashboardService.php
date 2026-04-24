<?php

namespace App\Services;

use App\Helpers\QueryFilter;
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

    /** Cache TTL in seconds: 5 minutes for filtered data */
    private const CACHE_TTL = 300;

    /** Max periods to return per chart (prevents truncation at ~1MB limit) */
    private const MAX_PERIODS = 30;

    /** Max waste types to return per chart */
    private const MAX_WASTES = 5;

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
    //    Only top N wastes + capped periods to prevent truncation
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

            // Step 1: Get top N waste IDs by total weight (within date range)
            $topWasteIds = DB::table('sort_items as si')
                ->join('sorts as s', 'si.sort_id', '=', 's.id')
                ->join('collects as c', 's.collect_id', '=', 'c.id')
                ->join('schedules as sc', 'c.schedule_id', '=', 'sc.id')
                ->when($dateFrom, fn($q) => $q->whereDate('sc.date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('sc.date', '<=', $dateTo))
                ->select(DB::raw('si.waste_id, SUM(si.weight) as total_weight'))
                ->groupBy('si.waste_id')
                ->orderByDesc('total_weight')
                ->limit(self::MAX_WASTES)
                ->pluck('waste_id')
                ->toArray();

            if (empty($topWasteIds)) {
                return [
                    'periods' => [],
                    'datasets' => [],
                ];
            }

            // Step 2: Get periods within range (capped to MAX_PERIODS)
            $periods = DB::table('sort_items as si')
                ->join('sorts as s', 'si.sort_id', '=', 's.id')
                ->join('collects as c', 's.collect_id', '=', 'c.id')
                ->join('schedules as sc', 'c.schedule_id', '=', 'sc.id')
                ->when($dateFrom, fn($q) => $q->whereDate('sc.date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('sc.date', '<=', $dateTo))
                ->whereIn('si.waste_id', $topWasteIds)
                ->select(DB::raw("{$periodSql} as period"))
                ->groupBy(DB::raw($periodSql))
                ->orderBy(DB::raw($periodSql))
                ->limit(self::MAX_PERIODS)
                ->pluck('period')
                ->toArray();

            if (empty($periods)) {
                return [
                    'periods' => [],
                    'datasets' => [],
                ];
            }

            // Step 3: Get weight data only for top wastes and capped periods
            // Build a "period IN (...)" condition for the specific group_by
            $periodCondition = match ($groupBy) {
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
                ->whereIn('si.waste_id', $topWasteIds)
                ->select(
                    DB::raw("{$periodCondition} as period"),
                    'w.id as waste_id',
                    'w.name as waste_name',
                    DB::raw('SUM(si.weight) as total_weight')
                )
                ->groupBy(DB::raw($periodCondition), 'w.id', 'w.name')
                ->orderBy('w.name')
                ->get();

            // Build waste_name -> waste_id map for consistent ordering
            $wasteOrder = DB::table('wastes as w')
                ->whereIn('w.id', $topWasteIds)
                ->orderByRaw("ARRAY_POSITION(ARRAY[" . implode(',', $topWasteIds) . "], w.id)")
                ->pluck('name', 'id')
                ->toArray();

            $datasets = [];
            foreach ($wasteOrder as $wasteId => $wasteName) {
                $itemsForWaste = $results->where('waste_id', $wasteId);
                $itemMap = $itemsForWaste->keyBy('period');
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
    // 2. Total pengangkutan (transport) + done/skip percentage
    //    Merged into single query for efficiency
    // ─────────────────────────────────────────────────────────────
    public function transportSummary(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $dateFrom = $this->filters->dateFrom();
            $dateTo = $this->filters->dateTo();

            // Single query: count done, skip, and total in one pass
            $statusCounts = DB::table('collects as c')
                ->join('schedules as s', 'c.schedule_id', '=', 's.id')
                ->when($dateFrom, fn($q) => $q->whereDate('s.date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('s.date', '<=', $dateTo))
                ->select('c.status', DB::raw('COUNT(*) as count'))
                ->groupBy('c.status')
                ->get()
                ->keyBy('status');

            $done = (int) ($statusCounts['DONE']->count ?? 0);
            $skip = (int) ($statusCounts['SKIP']->count ?? 0);

            $totalSchedules = DB::table('schedules')
                ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
                ->count();

            $totalCollected = $done + $skip;
            $donePct = $totalCollected > 0 ? round($done / $totalCollected * 100, 2) : 0;
            $skipPct = $totalCollected > 0 ? round($skip / $totalCollected * 100, 2) : 0;

            return [
                'done' => $done,
                'skip' => $skip,
                'not_collected' => $totalSchedules - $totalCollected,
                'total' => $totalSchedules,
                // Persentase DONE vs SKIP (merged from doneSkipPercentage)
                'done_pct' => $donePct,
                'skip_pct' => $skipPct,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────
    // 3. Status pemilahan
    //    Single query with conditional aggregation
    // ─────────────────────────────────────────────────────────────
    public function sortingStatus(): array
    {
        return $this->cached(__FUNCTION__, function () {
            $dateFrom = $this->filters->dateFrom();
            $dateTo = $this->filters->dateTo();

            // Single query: count sorted and total at once
            $result = DB::table('collects as c')
                ->join('schedules as s', 'c.schedule_id', '=', 's.id')
                ->leftJoin('sorts as so', 'c.id', '=', 'so.collect_id')
                ->where('c.status', 'DONE')
                ->when($dateFrom, fn($q) => $q->whereDate('s.date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('s.date', '<=', $dateTo))
                ->select(
                    DB::raw('COUNT(c.id) as total'),
                    DB::raw("COUNT(so.id) as sorted")
                )
                ->first();

            $total = (int) $result->total;
            $sorted = (int) $result->sorted;

            return [
                'sorted' => $sorted,
                'unsorted' => $total - $sorted,
                'total' => $total,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Top 5 material terberat
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
    // 5. Tren jadwal vs realisasi
    //    Capped to MAX_PERIODS to prevent large payloads
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

            // Subquery to get the N most recent periods, then fetch data for those
            $recentPeriods = DB::table('schedules as sc')
                ->when($dateFrom, fn($q) => $q->whereDate('sc.date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('sc.date', '<=', $dateTo))
                ->select(DB::raw("{$periodSql} as period"))
                ->groupBy(DB::raw($periodSql))
                ->orderBy(DB::raw($periodSql), 'desc')
                ->limit(self::MAX_PERIODS)
                ->pluck('period')
                ->toArray();

            // Reverse to get chronological order (oldest first)
            $recentPeriods = array_reverse($recentPeriods);

            if (empty($recentPeriods)) {
                return [];
            }

            // Build period list for SQL IN clause
            $periodPlaceholders = implode(',', array_fill(0, count($recentPeriods), '?'));

            $sql = "SELECT period, scheduled, collected,
                    CASE WHEN scheduled > 0 THEN ROUND(collected::numeric / scheduled * 100, 2) ELSE 0 END as collected_pct
                    FROM (
                        SELECT {$periodSql} as period,
                               COUNT(DISTINCT sc.id) as scheduled,
                               COUNT(DISTINCT c.id) as collected
                        FROM schedules sc
                        LEFT JOIN collects c ON sc.id = c.schedule_id
                        WHERE {$periodSql} IN ({$periodPlaceholders})";

            if ($dateFrom) {
                $sql .= " AND sc.date >= '{$dateFrom}'";
            }
            if ($dateTo) {
                $sql .= " AND sc.date <= '{$dateTo}'";
            }

            $sql .= " GROUP BY {$periodSql}
                    ) sub
                    ORDER BY period";

            $results = DB::select($sql, $recentPeriods);

            return array_map(fn($row) => [
                'period' => $row->period,
                'scheduled' => (int) $row->scheduled,
                'collected' => (int) $row->collected,
                'collected_pct' => (float) $row->collected_pct,
            ], $results);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // All-in-one dashboard
    // Note: averageWeightByWaste removed - was never used by frontend
    // Note: doneSkipPercentage merged into transportSummary
    // ─────────────────────────────────────────────────────────────
    public function dashboard(): array
    {
        return [
            'waste_weight_chart' => $this->wasteWeightChart(),
            'transport_summary' => $this->transportSummary(),
            'sorting_status' => $this->sortingStatus(),
            'top5_heaviest_wastes' => $this->top5HeaviestWastes(),
            'schedule_realization_trend' => $this->scheduleVsRealizationTrend(),
        ];
    }
}
