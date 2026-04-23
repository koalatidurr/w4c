<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Helpers\QueryFilter;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Dashboard", description="Dashboard reporting endpoints")
 */
class DashboardController extends Controller
{
    /**
     * Full dashboard data.
     * Returns all 7 dashboard sections in one response.
     *
     * @OA\Get(
     *     path="/api/dashboard",
     *     tags={"Dashboard"},
     *     summary="Get all dashboard data",
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date"), description="Filter from date"),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date"), description="Filter to date"),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"DONE","SKIP"})),
     *     @OA\Parameter(name="sort_status", in="query", @OA\Schema(type="string", enum={"sorted","unsorted"})),
     *     @OA\Parameter(name="trashbag_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="waste_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="group_by", in="query", @OA\Schema(type="string", enum={"day","month","year"}, default="day")),
     *     @OA\Response(response=200, description="Complete dashboard data")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = new QueryFilter($request->all());
        $service = new DashboardService($filters);

        return ApiResponse::success($service->dashboard());
    }

    /**
     * Sub-endpoint: waste weight chart only.
     *
     * @OA\Get(
     *     path="/api/dashboard/waste-weight",
     *     tags={"Dashboard"},
     *     summary="Waste weight chart",
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="group_by", in="query", @OA\Schema(type="string", enum={"day","month","year"}, default="day")),
     *     @OA\Response(response=200)
     * )
     */
    public function wasteWeight(Request $request): JsonResponse
    {
        $filters = new QueryFilter($request->all());
        $service = new DashboardService($filters);

        return ApiResponse::success($service->wasteWeightChart());
    }

    /**
     * Sub-endpoint: transport summary only.
     *
     * @OA\Get(
     *     path="/api/dashboard/transport",
     *     tags={"Dashboard"},
     *     summary="Transport summary",
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200)
     * )
     */
    public function transport(Request $request): JsonResponse
    {
        $filters = new QueryFilter($request->all());
        $service = new DashboardService($filters);

        return ApiResponse::success($service->transportSummary());
    }

    /**
     * Sub-endpoint: sorting status only.
     *
     * @OA\Get(
     *     path="/api/dashboard/sorting",
     *     tags={"Dashboard"},
     *     summary="Sorting status",
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200)
     * )
     */
    public function sorting(Request $request): JsonResponse
    {
        $filters = new QueryFilter($request->all());
        $service = new DashboardService($filters);

        return ApiResponse::success($service->sortingStatus());
    }

    /**
     * Sub-endpoint: top 5 heaviest wastes.
     *
     * @OA\Get(
     *     path="/api/dashboard/top-wastes",
     *     tags={"Dashboard"},
     *     summary="Top 5 heaviest wastes",
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200)
     * )
     */
    public function topWastes(Request $request): JsonResponse
    {
        $filters = new QueryFilter($request->all());
        $service = new DashboardService($filters);

        return ApiResponse::success($service->top5HeaviestWastes());
    }

    /**
     * Sub-endpoint: schedule vs realization trend.
     *
     * @OA\Get(
     *     path="/api/dashboard/trend",
     *     tags={"Dashboard"},
     *     summary="Schedule vs realization trend",
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="group_by", in="query", @OA\Schema(type="string", enum={"day","month","year"}, default="day")),
     *     @OA\Response(response=200)
     * )
     */
    public function trend(Request $request): JsonResponse
    {
        $filters = new QueryFilter($request->all());
        $service = new DashboardService($filters);

        return ApiResponse::success($service->scheduleVsRealizationTrend());
    }
}
