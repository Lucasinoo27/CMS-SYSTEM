<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Development Environment Optimizations
    |--------------------------------------------------------------------------
    */
    
    'cache' => [
        'driver' => 'redis',
        'prefix' => 'dev_cache_',
    ],
    
    'session' => [
        'driver' => 'redis',
        'lifetime' => 120,
    ],
    
    'queue' => [
        'default' => 'redis',
        'connections' => [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
                'queue' => 'default',
                'retry_after' => 90,
                'block_for' => null,
            ],
        ],
    ],
    
    'debug' => [
        'enabled' => true,
        'bar' => false, // Disable debug bar in development for better performance
    ],
]; 