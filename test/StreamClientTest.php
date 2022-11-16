<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\ClientTest;

use PhpPkg\Http\Client\StreamClient;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamClientTest
 */
class StreamClientTest extends TestCase
{
    public function testGet(): void
    {
        $c = StreamClient::create();
        $c->get('http://www.baidu.com');

        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
        // Content-Type: text/html
        $this->assertEquals('text/html', $c->getResponseHeader('Content-Type'));
    }
}
