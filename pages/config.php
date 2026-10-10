<?php
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'mahyarsa_telegram',
        'user'    => 'mahyarsa_telegram',
        'pass'    => 'M2347s2578',
        'charset' => 'utf8mb4',
    ],
    'paths' => [
        'logs'       => __DIR__ . '/logs',
        'queue'      => __DIR__ . '/storage/queue',
        'processing' => __DIR__ . '/storage/processing',
        'failed'     => __DIR__ . '/storage/failed',
        'lock'       => __DIR__ . '/storage/worker.lock',
    ],
    'api' => ['max_payload_size' => 20 * 1024 * 1024],
    'worker' => [
        'max_runtime'   => 50,
        'batch_size'    => 500,
        'max_files_run' => 2000,
    ],
    'log_level' => 'INFO',
];
