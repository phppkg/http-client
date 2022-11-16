<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\ClientTest;

use PhpPkg\Http\Client\FSockClient;
use PHPUnit\Framework\TestCase;

/**
 * Class FSockClientTest
 *
 * @package PhpPkg\Http\ClientTest
 */
class FSockClientTest extends TestCase
{
    public function testGet_fSock(): void
    {
        $c = FSockClient::create();
        $c->get('http://www.baidu.com');

        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
    }
}
