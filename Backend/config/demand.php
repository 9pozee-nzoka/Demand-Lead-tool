<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demand Engine Configuration
    |--------------------------------------------------------------------------
    */

    // Milliseconds to wait between provider API requests (rate limiting)
    'request_delay_ms' => env('DEMAND_REQUEST_DELAY_MS', 1200),

    // Minimum growth % to flag as RISING
    'rising_threshold'  => env('DEMAND_RISING_THRESHOLD', 20),

    // Growth % to flag as RAPIDLY_RISING
    'rapid_threshold'   => env('DEMAND_RAPID_THRESHOLD', 50),

    // Growth % to flag as SPIKE (short-term)
    'spike_threshold'   => env('DEMAND_SPIKE_THRESHOLD', 100),

    // Growth % below which trend is DECLINING
    'declining_threshold' => env('DEMAND_DECLINING_THRESHOLD', -20),

    // Minimum opportunity score to fire an alert
    'alert_min_score' => env('DEMAND_ALERT_MIN_SCORE', 60),

    // Opportunity scores (0-100) thresholds
    'score_very_high' => 80,
    'score_high'      => 60,
    'score_moderate'  => 40,

    // How many days of measurements to use for baseline calculations
    'baseline_days' => [
        'short'  => 7,
        'medium' => 30,
        'long'   => 90,
    ],

];
