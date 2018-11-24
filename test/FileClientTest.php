<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-24
 * Time: 18:59
 */

namespace PhpComp\Http\Client\Test;

use PhpComp\Http\Client\FileClient;
use PHPUnit\Framework\TestCase;

/**
 * Class FOpenClientTest
 * @covers \PhpComp\Http\Client\FileClient
 * @package PhpComp\Http\Client\Test
 */
class FileClientTest extends TestCase
{
    public function testGet()
    {
        $c = FileClient::create();
        $c->get('http://www.baidu.com');

        $this->assertFalse($c->hasError());
        $this->assertEquals(200, $c->getStatusCode());
    }
}
