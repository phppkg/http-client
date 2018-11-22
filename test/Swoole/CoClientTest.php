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
use Swoole\Runtime;

/**
 * Class CoClientTest
 * @covers \PhpComp\Http\Client\Swoole\CoClient
 * @package PhpComp\Http\Client\Test\Swoole
 */
class CoClientTest extends TestCase
{
    /*
    protected function setUp()
    {
        Runtime::enableCoroutine(true);
    }

    protected function tearDown()
    {
        Runtime::enableCoroutine(false);
    }*/

    public function testGet()
    {
        \go(function () {
            $c = CoClient::create();
            $c->get('http://www.baidu.com');

            $this->assertFalse($c->hasError());
        });

    }
}
