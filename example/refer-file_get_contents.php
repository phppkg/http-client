<?php declare(strict_types=1);

require dirname(__DIR__) . '/test/bootstrap.php';

/**
 * # example 1 获取一个页面并发送 POST 数据
 *
 * @link https://secure.php.net/manual/zh/context.http.php#refsect1-context.http-examples
 */

$postdata = http_build_query(
    [
        'var1' => 'some content',
        'var2' => 'doh'
    ]
);

$opts = [
    'http' =>
        [
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        ]
];

$context = stream_context_create($opts);
$result  = file_get_contents('http://example.com/submit.php', false, $context);

