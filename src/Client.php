<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/21
 * Time: 3:27 PM
 */

namespace PhpComp\Http\Client;

use PhpComp\Http\Client\Curl\Curl;

/**
 * Class Client
 * @package PhpComp\Http\Client
 */
class Client
{
    /**
     * @param array $config
     */
    public static function factory(array $config)
    {

    }

    /**
     * @param array $options
     * @return Curl
     */
    public static function newCURL(array $options)
    {
        return Curl::create($options);
    }
}
