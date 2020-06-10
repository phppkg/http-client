<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client;

use PhpComp\Http\Client\Curl\CurlClient;
use PhpComp\Http\Client\Swoole\CoClient;
use PhpComp\Http\Client\Swoole\CoClient2;
use RuntimeException;
use InvalidArgumentException;
use function method_exists;

/**
 * Class Client
 * @package PhpComp\Http\Client
 * @method static ClientInterface get(string $url, $params = null, array $headers = [], array $options = [])
 * @method static ClientInterface delete(string $url, $params = null, array $headers = [], array $options = [])
 * @method static ClientInterface head(string $url, $params = null, array $headers = [], array $options = [])
 * @method static ClientInterface options(string $url, $params = null, array $headers = [], array $options = [])
 * @method static ClientInterface post(string $url, $data = null, array $headers = [], array $options = [])
 * @method static ClientInterface put(string $url, $data = null, array $headers = [], array $options = [])
 * @method static ClientInterface patch(string $url, $data = null, array $headers = [], array $options = [])
 * @method static ClientInterface request(string $url, $data = null, array $headers = [], array $options = [])
 */
class Client
{
    /**
     * supported drivers
     * @var ClientInterface[]
     */
    private static $drivers = [
        'co' => CoClient::class,
        'curl' => CurlClient::class,
        'stream' => StreamClient::class,
        'fsock' => FSockClient::class,
        'fopen' => FOpenClient::class,
        'file' => FileClient::class,
        'co2' => CoClient2::class,
    ];

    /**
     * @var ClientInterface
     */
    private static $defaultDriver;

    /**
     * config data for $defaultDriver
     * @var array
     */
    private static $defaultConfig = [];

    /**
     * @param array $config
     * [
     *  'driver' => 'curl', // curl, stream, fsock, fopen, file, co, co2
     *  // ...
     * ]
     * @return ClientInterface
     */
    public static function factory(array $config): ClientInterface
    {
        $name = $config['driver'] ?? '';

        if (!$class = self::$drivers[$name] ?? '') {
            // auto select
            foreach (self::$drivers as $driverClass) {
                if ($driverClass::isAvailable()) {
                    $class = $driverClass;
                }
            }
        } else {
            // remove key: 'driver'
            unset($config['driver']);
        }

        if ($class === '') {
            throw new RuntimeException('no driver is available!');
        }

        return $class::create($config);
    }

    /**
     * @param array $config
     */
    public static function configDefault(array $config): void
    {
        self::$defaultConfig = $config;
    }

    /**
     * @return ClientInterface
     */
    public static function getDefaultDriver(): ClientInterface
    {
        return self::$defaultDriver;
    }

    /**
     * @param ClientInterface $defaultDriver
     */
    public static function setDefaultDriver(ClientInterface $defaultDriver): void
    {
        self::$defaultDriver = $defaultDriver;
    }

    /**
     * @param string $method
     * @param array $args
     * @return ClientInterface
     */
    public static function __callStatic(string $method, array $args)
    {
        if (!$client = self::$defaultDriver) {
            // has config, create driver instance from config.
            if (!$config = self::$defaultConfig) {
                throw new RuntimeException('must be setting default client driver before call');
            }

            $client = self::factory($config);
        }

        if (method_exists($client, $method)) {
            return $client->reset()->$method(...$args);
        }

        throw new InvalidArgumentException('call invalid class method: ' . $method);
    }
}
