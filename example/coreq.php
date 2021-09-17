<?php

use PhpComp\Http\Client\Client;
use PhpComp\Http\Client\ClientConst;
use PhpComp\Http\Client\Swoole\CoClient;

require dirname(__DIR__) . '/test/bootstrap.php';

$client = Client::factory([
    'driver' => CoClient::driverName(),
]);
$client->setUserAgent(ClientConst::USERAGENT_CURL);

// debug
// $client->setDebug(true);
// $client->setOption('logFile', './client-req.log');

$client->get('https://cht.sh/php/array_shift');

vdump(
    $client->getDriverName(),
    $client->getResponseHeaders()
);

echo $client->getResponseBody();
