<?php declare(strict_types=1);

require dirname(__DIR__) . '/test/bootstrap.php';

/**
 * @link https://secure.php.net/manual/zh/context.http.php#110449
 */

$stream = stream_context_create([
    'http' => [
        'method'          => 'GET',
        'timeout'         => 20,
        'header'          => 'User-agent: Myagent',
        'proxy'           => 'tcp://my-proxy.localnet:3128',
        'request_fulluri' => true /* without this option we get an HTTP error! */
    ]
]);

if ($fp = fopen('http://example.com', 'rb', false, $stream)) {
    print 'well done';
}

