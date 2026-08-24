<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KeywordMeasurement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrendController extends Controller
{
    /**
     * GET /api/v1/trends
     * All recent measurements for the authenticated org's keywords.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;
        $days  = min((int) $request->query('days', 30), 365);

        $measurements = KeywordMeasurement::whereHas('keyword.project', fn ($q) => $q->where('organization_id', $orgId))
            ->inPeriod($days)
            ->with('keyword:id,keyword,intent')
            ->orderByDesc('date')
            ->paginate(50);

        return response()->json($measurements);
    }

    /**
     * GET /api/v1/trends/rising
     * Keywords currently trending up.
     */
    public function rising(Request $request): JsonResponse
    {
        $orgId     = $request->user()->organization_id;
        $threshold = (float) $request->query('min_growth', 20);

        $rising = KeywordMeasurement::whereHas('keyword.project', fn ($q) => $q->where('organization_id', $orgId))
            ->where('growth', '>=', $threshold)
            ->inPeriod(7)
            ->with('keyword:id,keyword,intent,project_id')
            ->orderByDesc('growth')
            ->limit(50)
            ->get();

        return response()->json($rising);
    }
}
