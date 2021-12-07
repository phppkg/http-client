<?php

use PhpPkg\Http\Client\Client;
use PhpPkg\Http\Client\ClientConst;

require dirname(__DIR__) . '/test/bootstrap.php';

$client = Client::factory([]);
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
