<?php
/**
 * @link https://secure.php.net/manual/zh/context.http.php#refsect1-context.http-examples
 */

$postdata = http_build_query(
    array(
        'var1' => 'some content',
        'var2' => 'doh'
    )
);

$opts = array('http' =>
    array(
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postdata
    )
);

$context = stream_context_create($opts);
$result = file_get_contents('http://example.com/submit.php', false, $context);

?>

<?php
/**
 * @link https://secure.php.net/manual/zh/context.http.php#110449
 */

$stream = stream_context_create(Array('http' => Array('method' => 'GET',
    'timeout' => 20,
    'header' => 'User-agent: Myagent',
    'proxy' => 'tcp://my-proxy.localnet:3128',
    'request_fulluri' => True /* without this option we get an HTTP error! */
)));

if ($fp = fopen('http://example.com', 'r', false, $stream)) {
    print 'well done';
}
?>
