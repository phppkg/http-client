<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\ClientTest;

use PhpPkg\Http\Client\Curl\CurlClient;
use PHPUnit\Framework\TestCase;

/**
 * Class CurlClientTest
 *
 * @package PhpPkg\Http\ClientTest
 */
class CurlClientTest extends TestCase
{
    public function testGet(): void
    {
        // http
        $c = CurlClient::create();
        $c->decodeGzip()
            // ->onlyReturnBody()
            ->get('http://www.baidu.com');

        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
        $this->assertEquals('curl', $c->getDriverName());
        $this->assertNotEmpty($c->getBody());
        $this->assertNotEmpty($c->getResponseHeaders());

        // https
        $c = CurlClient::create(['baseUrl' => 'https://www.baidu.com']);
        $c->get('');

        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
        $this->assertNotEmpty($c->getBody());
        $this->assertNotEmpty($c->getResponseHeaders());
    }

    public function testDownload(): void
    {
        $c    = CurlClient::create();
        $url  = 'https://github.com/phppkg/http-client/archive/master.zip';
        $file = __DIR__ . '/down-test.zip';
        $ok   = $c->download($url, $file);

        $this->assertTrue($ok);
        $this->assertFileExists($file);
    }
}
