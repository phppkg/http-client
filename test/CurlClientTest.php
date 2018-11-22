<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/22
 * Time: 5:19 PM
 */

namespace PhpComp\Http\Client\Test;

use PhpComp\Http\Client\Curl\Curl;
use PHPUnit\Framework\TestCase;

/**
 * Class CurlClientTest
 * @covers \PhpComp\Http\Client\Curl\Curl
 * @package PhpComp\Http\Client\Test
 */
class CurlClientTest extends TestCase
{
    public function testBasic()
    {
        // http
        $c = Curl::create();
        $c
            ->decodeGzip()
            // ->onlyReturnBody()
            ->get('http://www.baidu.com');

        $this->assertFalse($c->hasError());
        $this->assertEquals(0, $c->getErrNo());
        $this->assertEquals('', $c->getError());
        $this->assertEquals(200, $c->getStatusCode());
        $this->assertNotEmpty($c->getResponse());
        $this->assertNotEmpty($c->getBody());
        $this->assertNotEmpty($c->getResponseHeaders());

        // https
        $c = Curl::create(['baseUrl' => 'https://www.baidu.com']);
        $c->get('');

        $this->assertFalse($c->hasError());
        $this->assertEquals(0, $c->getErrNo());
        $this->assertEquals('', $c->getError());
        $this->assertEquals(200, $c->getStatusCode());
        $this->assertNotEmpty($c->getBody());
        $this->assertNotEmpty($c->getResponseHeaders());
    }

    public function testDownload()
    {
        $c = Curl::create();
        $url = 'https://github.com/php-comp/http-client/archive/master.zip';
        $file = __DIR__.'/down-test.zip';
        $ok = $c->download($url, $file);

        $this->assertTrue($ok);
        $this->assertFileExists($file);
    }
}
