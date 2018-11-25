<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/22
 * Time: 5:19 PM
 */

namespace PhpComp\Http\Client\Test;

use PhpComp\Http\Client\Curl\CurlClient;
use PHPUnit\Framework\TestCase;

/**
 * Class CurlClientTest
 * @covers \PhpComp\Http\Client\Curl\CurlClient
 * @package PhpComp\Http\Client\Test
 */
class CurlClientTest extends TestCase
{
    public function testGet()
    {
        // http
        $c = CurlClient::create();
        $c
            ->decodeGzip()
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

    public function testDownload()
    {
        $c = CurlClient::create();
        $url = 'https://github.com/php-comp/http-client/archive/master.zip';
        $file = __DIR__ . '/down-test.zip';
        $ok = $c->download($url, $file);

        $this->assertTrue($ok);
        $this->assertFileExists($file);
    }
}
