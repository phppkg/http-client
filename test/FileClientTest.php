<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\ClientTest;

use PhpPkg\Http\Client\FileClient;
use PHPUnit\Framework\TestCase;

/**
 * Class FOpenClientTest
 *
 * @package PhpPkg\Http\ClientTest
 */
class FileClientTest extends TestCase
{
    public function testGet(): void
    {
        $c = FileClient::create();
        $c->get('http://www.baidu.com');

        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
    }
}
