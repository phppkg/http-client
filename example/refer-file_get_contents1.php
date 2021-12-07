<?php declare(strict_types=1);

require dirname(__DIR__) . '/test/bootstrap.php';

/**
 * @see https://secure.php.net/manual/zh/context.http.php#114867
 */

// php 5.4 : array syntax and header option with array value
$data = file_get_contents('http://www.example.com/', false, stream_context_create([
    'http' => [
        'protocol_version' => 1.1,
        'header'           => [
            'Connection: close',
        ],
    ],
]));

