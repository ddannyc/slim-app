<?php
return [
    'settings' => [
        'displayErrorDetails' => true,
        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
            'cache_path' => false//__DIR__ . '/../cache/templates/',
        ],
        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
        ],
        // my db
        'db_config' => [
            'debug' => false,
            'dsn' => 'mysql:host=localhost;dbname=db_yanzi;charset=utf8',
            'user' => 'root',
            'password' => ''
        ],
        // static path
        'path_static' => __DIR__ . '/../public/static/',
    ],
];