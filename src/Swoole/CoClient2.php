<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-30
 * Time: 17:26
 */

namespace PhpComp\Http\Client\Swoole;

use PhpComp\Http\Client\AbstractClient;
use Swoole\Coroutine\Http2\Client;

/**
 * Class CoClient2 - http2 client
 * @package PhpComp\Http\Client\Swoole
 * @link https://wiki.swoole.com/wiki/page/856.html
 */
class CoClient2 extends AbstractClient
{
    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return \class_exists(Client::class);
    }

    /**
     * Send request to remote URL
     * @param $url
     * @param array $data
     * @param string $method
     * @param array $headers
     * @param array $options
     * @return self
     */
    public function request(string $url, $data = null, string $method = self::GET, array $headers = [], array $options = [])
    {
        return $this;
    }
}
