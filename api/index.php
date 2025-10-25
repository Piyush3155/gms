<?php
require_once 'config.php';

// Simple API documentation
$api_info = [
    'name' => 'Gym Management System API',
    'version' => '1.0.0',
    'description' => 'REST API for Gym Management System',
    'authentication' => 'Use X-API-Key header or api_key query parameter',
    'endpoints' => [
        [
            'path' => '/api/members.php',
            'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'description' => 'Manage gym members',
            'parameters' => [
                'GET' => 'id (optional) - Get specific member or all members',
                'POST' => 'name, email, contact, join_date, plan_id',
                'PUT' => 'id (query), name, email, contact, status',
                'DELETE' => 'id (query)'
            ]
        ],
        [
            'path' => '/api/attendance.php',
            'methods' => ['GET', 'POST'],
            'description' => 'Manage attendance records',
            'parameters' => [
                'GET' => 'user_id (optional), date (optional)',
                'POST' => 'user_id, date, role, check_in, status'
            ]
        ],
        [
            'path' => '/api/payments.php',
            'methods' => ['GET', 'POST'],
            'description' => 'Manage payment records',
            'parameters' => [
                'GET' => 'id (optional), member_id (optional), status (optional)',
                'POST' => 'member_id, amount, plan_id, payment_date, method'
            ]
        ]
    ],
    'example_usage' => [
        'curl -H "X-API-Key: your_api_key" https://yourdomain.com/api/members.php',
        'curl -X POST -H "Content-Type: application/json" -H "X-API-Key: your_api_key" -d \'{"name":"John Doe","email":"john@example.com"}\' https://yourdomain.com/api/members.php'
    ]
];

header('Content-Type: application/json');
echo json_encode($api_info, JSON_PRETTY_PRINT);
?>