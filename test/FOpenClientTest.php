<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
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
    public function testGet(): void
    {
        $c = FOpenClient::create();
        $c->get('http://www.baidu.com');

        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
    }
}
