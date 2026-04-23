<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Waste4Change API',
                'description' => 'Waste Management Reporting System API',
                'version' => '1.0.0',
            ],
            'routes' => [
                'api' => 'api/documentation',
                'docs' => 'docs',
            ],
            'paths' => [
                base_path('openapi.yaml'),
            ],
        ],
    ],
];
