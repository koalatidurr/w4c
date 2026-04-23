<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Helpers\QueryFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Schedules", description="Schedule management endpoints")
 */
class ScheduleController extends Controller
{
    /**
     * List schedules with pagination and date filter.
     *
     * @OA\Get(
     *     path="/api/schedules",
     *     tags={"Schedules"},
     *     summary="List all schedules",
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date"), description="Filter from date"),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date"), description="Filter to date"),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated schedule list")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = new QueryFilter($request->all());

        $query = Schedule::with(['client', 'collect.sort'])
            ->when($filters->dateFrom(), fn($q) => $q->whereDate('date', '>=', $filters->dateFrom()))
            ->when($filters->dateTo(), fn($q) => $q->whereDate('date', '<=', $filters->dateTo()))
            ->orderBy('date', 'desc');

        $paginator = $query->paginate($filters->perPage());

        return ApiResponse::paginated($paginator);
    }

    /**
     * Get single schedule with full details.
     *
     * @OA\Get(
     *     path="/api/schedules/{id}",
     *     tags={"Schedules"},
     *     summary="Get schedule detail",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Schedule detail with collect and sort"),
     *     @OA\Response(response=404, description="Schedule not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $schedule = Schedule::with(['client', 'collect.collectItems.trashbag', 'collect.sort.sortItems.waste'])
            ->find($id);

        if (! $schedule) {
            return ApiResponse::error('Schedule not found', 404);
        }

        return ApiResponse::success(new ScheduleResource($schedule));
    }
}
