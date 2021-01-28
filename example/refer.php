<?php declare(strict_types=1);
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

?>

<?php
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
$stream  = fopen($url, 'r', false, $context);

// header information as well as meta data
// about the stream
var_dump(stream_get_meta_data($stream));

// actual data at $url
var_dump(stream_get_contents($stream));
fclose($stream);
?>

<?php
/**
 * @see https://secure.php.net/manual/zh/context.http.php#114867
 */

// php 5.4 : array syntax and header option with array value
$data = file_get_contents('http://www.example.com/', null, stream_context_create([
    'http' => [
        'protocol_version' => 1.1,
        'header'           => [
            'Connection: close',
        ],
    ],
]));
?>

<?php
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

if ($fp = fopen('http://example.com', 'r', false, $stream)) {
    print 'well done';
}
?>
