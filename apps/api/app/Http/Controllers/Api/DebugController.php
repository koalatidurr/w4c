<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collect;
use App\Models\Schedule;
use App\Models\Sort;
use Illuminate\Http\JsonResponse;

/**
 * Temporary debug controller — remove once the data issue is resolved.
 */
class DebugController extends Controller
{
    /**
     * Return raw counts and a sample of schedules to verify seeder output.
     *
     * Checks:
     *  - schedules_count  → Schedule::count()
     *  - collects_count   → Collect::count()
     *  - sorts_count      → Sort::count()
     *  - sample_schedules → first 5 rows, all columns, no filtering
     */
    public function schedules(): JsonResponse
    {
        return response()->json([
            'schedules_count' => Schedule::count(),
            'collects_count'  => Collect::count(),
            'sorts_count'     => Sort::count(),
            'sample_schedules' => Schedule::limit(5)->get(),
        ]);
    }
}
