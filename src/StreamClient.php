<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/9/11
 * Time: 下午8:04
 */

namespace PhpComp\Http\Client;

/**
 * Class StreamClient
 * @package PhpComp\Http\Client
 */
class StreamClient extends AbstractClient
{
    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        // TODO: Implement isAvailable() method.
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
        // TODO: Implement request() method.
    }
}
