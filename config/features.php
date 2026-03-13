<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flags for enabling/disabling functionality
    | without deploying code changes.
    |
    */
    
    'course_builder_v2' => env('FEATURE_COURSE_BUILDER_V2', false),
    'course_player_v2' => env('FEATURE_COURSE_PLAYER_V2', false),
    'dashboard_v2' => env('FEATURE_DASHBOARD_V2', false),
];
