<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\ClientTest;

use PhpPkg\Http\Client\FOpenClient;
use PHPUnit\Framework\TestCase;

/**
 * Class FOpenClientTest
 *
 * @package PhpPkg\Http\ClientTest
 */
class FOpenClientTest extends TestCase
{
    public function testGet(): void
    {
        $c = FOpenClient::create();
        $c->get('http://www.baidu.com');

        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
    }
}
