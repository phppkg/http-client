<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-24
 * Time: 18:59
 */

namespace PhpComp\Http\Client\Test;

use PhpComp\Http\Client\FOpenClient;
use PHPUnit\Framework\TestCase;

/**
 * Class FOpenClientTest
 * @covers \PhpComp\Http\Client\FOpenClient
 * @package PhpComp\Http\Client\Test
 */
class FOpenClientTest extends TestCase
{
    public function testGet()
    {
        $c = FOpenClient::create();
        $c->get('http://www.baidu.com');

        $this->assertFalse($c->hasError());
        $this->assertEquals(200, $c->getStatusCode());
    }
}
