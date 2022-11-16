<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\ClientTest;

use PhpPkg\Http\Client\Client;
use PhpPkg\Http\Client\ClientConst;
use PHPUnit\Framework\TestCase;

/**
 * Class ClientTest
 *
 * @package PhpPkg\Http\ClientTest
 */
class ClientTest extends TestCase
{
    public function testGet_factory(): void
    {
        $url    = 'https://httpbin.org';
        $client = Client::factory([
            'driver'  => Client::DRIVER_FSOCK,
            'baseUrl' => $url,
            'debug'   => true,
        ]);
        $this->assertEquals(Client::DRIVER_FSOCK, $client->getDriverName());

        Client::setDefaultDriver($client);

        $c = Client::get('/get', null, [
            ClientConst::USERAGENT => ClientConst::USERAGENT_CURL,
        ]);

        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
        $this->assertEquals('fsock', $c->getDriverName());
        $this->assertNotEmpty($c->getResponseBody());
        $this->assertNotEmpty($c->getResponseHeaders());
        $this->assertNotEmpty($c->getDebugInfo());
    }
}
