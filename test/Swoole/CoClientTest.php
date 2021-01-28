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
use Swoole\Timer;
use function go;
use function swoole_event_exit;

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
        if (!CoClient::isAvailable()) {
            return;
        }

        // parent::tearDown();
        Timer::after(3 * 1000, function (): void {
            swoole_event_exit();
        });
    }

    public function testGet(): void
    {
        if (!CoClient::isAvailable()) {
            return;
        }

        // http
        $cid = go(function (): void {
            $c = CoClient::create();
            $c->get('http://www.baidu.com');

            $this->assertFalse($c->isDefer());
            $this->assertFalse($c->isError());
            $this->assertNotEmpty($c->getBody());
            $this->assertNotEmpty($c->getResponseHeaders());
            // \swoole_event_exit();
        });

        $this->assertTrue($cid > 0);
    }

    public function testDefer(): void
    {
        if (!CoClient::isAvailable()) {
            return;
        }

        $cid = go(function (): void {
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

            // \swoole_event_exit();
        });

        $this->assertTrue($cid > 0);
    }
}
