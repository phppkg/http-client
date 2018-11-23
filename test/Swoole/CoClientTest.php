<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/22
 * Time: 6:51 PM
 */

namespace PhpComp\Http\Client\Test\Swoole;

use PhpComp\Http\Client\Swoole\CoClient;
use PHPUnit\Framework\TestCase;
use Swoole\Timer;

/**
 * Class CoClientTest
 * @covers \PhpComp\Http\Client\Swoole\CoClient
 * @package PhpComp\Http\Client\Test\Swoole
 */
class CoClientTest extends TestCase
{
    protected function tearDown()
    {
        // parent::tearDown();
        Timer::after(3 * 1000, function () {
            \swoole_event_exit();
        });
    }

    public function testGet()
    {
        if (!CoClient::isAvailable()) {
            return;
        }

        // http
        $cid = \go(function () {
            $c = CoClient::create();
            $c->get('http://www.baidu.com');

            $this->assertFalse($c->isDefer());
            $this->assertFalse($c->hasError());
            $this->assertNotEmpty($c->getBody());
            $this->assertNotEmpty($c->getResponseHeaders());
            // \swoole_event_exit();
        });

        $this->assertTrue($cid > 0);
    }

    public function testDefer()
    {
        if (!CoClient::isAvailable()) {
            return;
        }

        $cid = \go(function () {
            $c = CoClient::create();
            $c->setDefer()->get('http://www.baidu.com');

            $this->assertTrue($c->isDefer());
            $this->assertFalse($c->hasError());
            $this->assertEmpty($c->getBody());
            $this->assertEmpty($c->getResponseHeaders());

            $c->receive();

            $this->assertFalse($c->hasError());
            $this->assertNotEmpty($c->getBody());
            $this->assertNotEmpty($c->getResponseHeaders());

            // \swoole_event_exit();
        });

        $this->assertTrue($cid > 0);
    }
}
