<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\ClientTest;

use PhpComp\Http\Client\StreamClient;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamClientTest
 *
 * @package PhpComp\Http\ClientTest
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
