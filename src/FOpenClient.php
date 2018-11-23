<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-23
 * Time: 19:31
 */

namespace PhpComp\Http\Client;

/**
 * Class FOpenClient - powered by func fopen()
 * @package PhpComp\Http\Client
 */
class FOpenClient extends AbstractClient
{
    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        // return \function_exists('fopen');
        return false;
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