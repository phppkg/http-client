<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\ClientTest;

use PhpComp\Http\Client\FileClient;
use PHPUnit\Framework\TestCase;

/**
 * Class FOpenClientTest
 *
 * @package PhpComp\Http\ClientTest
 */
class FileClientTest extends TestCase
{
    public function testGet(): void
    {
        $c = FileClient::create();
        $c->get('http://www.baidu.com');

        $this->assertFalse($c->isError());
        $this->assertEquals(200, $c->getStatusCode());
    }
}
