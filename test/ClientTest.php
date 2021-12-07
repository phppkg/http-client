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
use PHPUnit\Framework\TestCase;

/**
 * Class ClientTest
 *
 * @package PhpPkg\Http\ClientTest
 */
class ClientTest extends TestCase
{
    public function testGet(): void
    {
        $url    = 'http://www.baidu.com';
        $client = Client::factory([
            'driver'  => 'fsock',
            'baseUrl' => $url,
        ]);
        $this->assertEquals('fsock', $client->getDriverName());

        Client::setDefaultDriver($client);

        $c = Client::get('');
        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
        $this->assertEquals('fsock', $c->getDriverName());
        $this->assertNotEmpty($c->getResponseBody());
        $this->assertNotEmpty($c->getResponseHeaders());
    }
}
