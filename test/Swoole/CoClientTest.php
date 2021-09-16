<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client\Test\Swoole;

use PhpComp\Http\Client\Swoole\CoClient;
use PHPUnit\Framework\TestCase;
use function Swoole\Coroutine\run;

/**
 * Class CoClientTest
 *
 * @covers  \PhpComp\Http\Client\Swoole\CoClient
 * @package PhpComp\Http\Client\Test\Swoole
 */
class CoClientTest extends TestCase
{
    protected function tearDown(): void
    {
        // if (!CoClient::isAvailable()) {
        //     return;
        // }
    }

    public function testGet(): void
    {
        if (!CoClient::isAvailable()) {
            return;
        }

        // http
        $cid = run(function (): void {
            $c = CoClient::create();
            $c->get('http://www.baidu.com');
            // $c->get('https://cht.sh/php');

            $this->assertFalse($c->isDefer());
            $this->assertFalse($c->isError());
            $this->assertNotEmpty($c->getBody());
            $this->assertNotEmpty($resHeaders = $c->getResponseHeaders());
            vdump($resHeaders);
        });

        $this->assertTrue($cid > 0);
    }

    public function testDefer(): void
    {
        if (!CoClient::isAvailable()) {
            return;
        }

        $cid = run(function (): void {
            $c = CoClient::create();
            $c->setDefer()->get('http://www.baidu.com');

            $this->assertTrue($c->isDefer());
            $this->assertFalse($c->isError());
            $this->assertEmpty($c->getBody());
            $this->assertEmpty($c->getResponseHeaders());

            $c->receive();

            $this->assertFalse($c->isError());
            $this->assertNotEmpty($c->getBody());
            $this->assertNotEmpty($c->getResponseHeaders());
        });

        $this->assertTrue($cid > 0);
    }
}
