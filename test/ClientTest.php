<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-25
 * Time: 14:21
 */

namespace PhpComp\Http\Client\Test;

use PHPUnit\Framework\TestCase;
use PhpComp\Http\Client\Client;

/**
 * Class ClientTest
 * @covers \PhpComp\Http\Client\Client
 * @package PhpComp\Http\Client\Test
 */
class ClientTest extends TestCase
{
    public function testGet()
    {
        $url = 'http://www.baidu.com';
        $client = Client::factory([
            'driver' => 'fsock',
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