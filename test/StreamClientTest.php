<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-23
 * Time: 16:54
 */

namespace PhpComp\Http\Client\Test;

use PhpComp\Http\Client\StreamClient;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamClientTest
 * @covers \PhpComp\Http\Client\StreamClient
 * @package PhpComp\Http\Client\Test
 */
class StreamClientTest extends TestCase
{
    public function testGet()
    {
        $c = StreamClient::create();
        $c->get('http://www.baidu.com');

        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
        // Content-Type: text/html
        $this->assertEquals('text/html', $c->getResponseHeader('Content-Type'));
    }
}
