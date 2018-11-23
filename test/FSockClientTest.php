<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-23
 * Time: 16:54
 */

namespace PhpComp\Http\Client\Test;

use PhpComp\Http\Client\FSockClient;
use PHPUnit\Framework\TestCase;

/**
 * Class FSockClientTest
 * @covers \PhpComp\Http\Client\FSockClient
 * @package PhpComp\Http\Client\Test
 */
class FSockClientTest extends TestCase
{
    public function testGet()
    {
        $c = FSockClient::create();
        $c->get('http://www.baidu.com');

        $this->assertFalse($c->hasError());
        $this->assertEquals(200, $c->getStatusCode());
    }
}
