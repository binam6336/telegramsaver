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

    'api' => [
        'max_payload_size' => 20 * 1024 * 1024, // 20MB
    ],

    'worker' => [
        'max_runtime'     => 50,   // ثانیه (کمتر از 60 برای کرون دقیقه‌ای)
        'batch_size'      => 500,  // تعداد رکورد در هر INSERT
        'max_files_run'   => 2000, // حداکثر فایل در هر اجرا
    ],

    'log_level' => 'INFO', // DEBUG | INFO | WARNING | ERROR | CRITICAL
];
