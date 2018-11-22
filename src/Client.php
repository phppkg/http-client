<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/21
 * Time: 3:27 PM
 */

namespace PhpComp\Http\Client;

use PhpComp\Http\Client\Curl\Curl;
use PhpComp\Http\Client\Swoole\CoClient;
use PhpComp\Http\Client\Swoole\CoClient2;

/**
 * Class Client
 * @package PhpComp\Http\Client
 */
class Client
{
    /**
     * @var ClientInterface[]
     */
    protected static $drivers = [
        'co' => CoClient::class,
        'curl' => Curl::class,
        'fsock' => FSockClient::class,
        'stream' => StreamClient::class,
        'co2' => CoClient2::class,
    ];

    /**
     * @param array $config
     * [
     *  'driver' => 'curl', // 'co', 'co2'
     *  // ...
     * ]
     * @return ClientInterface
     */
    public static function factory(array $config): ClientInterface
    {
        $name = '';
        if (isset($config['driver'])) {
            $name = $config['driver'];
        }

        if (!$class = self::$drivers[$name] ?? '') {
            // auto select
            foreach (self::$drivers as $driverClass) {
                if ($driverClass::isAvailable()) {
                    $class = $driverClass;
                }
            }
        }

        if ($class === '') {
            throw new \RuntimeException('no driver is available');
        }

        return $class::create($config);
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
