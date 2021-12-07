<?php declare(strict_types=1);

require dirname(__DIR__) . '/test/bootstrap.php';

/**
 * # example 2 忽略重定向并获取 header 和内容
 *
 * @link https://secure.php.net/manual/zh/context.http.php#refsect1-context.http-examples
 */

$url = 'http://www.example.org/header.php';

$opts = [
    'http' =>
        [
            'method'        => 'GET',
            'max_redirects' => '0',
            'ignore_errors' => '1'
        ]
];

$context = stream_context_create($opts);
$stream  = fopen($url, 'rb', false, $context);

// header information as well as meta data
// about the stream
vdump(stream_get_meta_data($stream));

// actual data at $url
vdump(stream_get_contents($stream));
fclose($stream);

