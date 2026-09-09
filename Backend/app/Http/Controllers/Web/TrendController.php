<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Demand\TrendEngine;
use Illuminate\Http\Request;

class TrendController extends Controller
{
    protected TrendEngine $trendEngine;

    public function __construct(TrendEngine $trendEngine)
    {
        $this->trendEngine = $trendEngine;
    }

    public function index(Request $request)
    {
        $organizationId = $request->user()->organization_id;

        // Get all trends
        $trendsData = $this->trendEngine->detectAllTrends($organizationId);

        // Get filtered trends based on state
        $filter = $request->get('filter', 'all');
        
        return view('trends.index', [
            'trendsData' => $trendsData,
            'filter' => $filter,
            'trending' => $this->trendEngine->getTrendingKeywords($organizationId, 5),
            'emerging' => $this->trendEngine->getEmergingKeywords($organizationId, 5),
            'declining' => $this->trendEngine->getDecliningKeywords($organizationId, 5),
        ]);
    }
}
