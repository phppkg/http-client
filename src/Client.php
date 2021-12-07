<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\Client;

use InvalidArgumentException;
use PhpPkg\Http\Client\Curl\CurlClient;
use PhpPkg\Http\Client\Swoole\CoClient;
use PhpPkg\Http\Client\Swoole\CoClient2;
use RuntimeException;
use function method_exists;

/**
 * Class Client
 *
 * @package PhpPkg\Http\Client
 *
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
     * The supported drivers
     *
     * @var ClientInterface[]
     */
    private static $drivers = [
        'curl'   => CurlClient::class,
        'stream' => StreamClient::class,
        'fsock'  => FSockClient::class,
        'fopen'  => FOpenClient::class,
        'file'   => FileClient::class,
        'co'     => CoClient::class,
        'co2'    => CoClient2::class,
    ];

    /**
     * @var ClientInterface
     */
    private static $defaultDriver;

    /**
     * config data for $defaultDriver
     *
     * @var array
     */
    private static $defaultConfig = [];

    /**
     * Quick create an client
     *
     *  [
     *      'driver' => 'curl', // curl, stream, fsock, fopen, file, co, co2
     *      // ...
     *  ]
     *
     * @param array $config more see {@see AbstractClient::$options}
     * @psalm-param array{driver:string, } $config
     *
     * @return ClientInterface|AbstractClient
     */
    public static function factory(array $config): ClientInterface
    {
        $name  = $config['driver'] ?? '';
        $class = self::$drivers[$name] ?? '';

        if (!$class) {
            // auto select
            foreach (self::$drivers as $driverClass) {
                if ($driverClass::isAvailable()) {
                    $class = $driverClass;
                    break;
                }
            }
        }

        // remove key: 'driver'
        if ($name) {
            unset($config['driver']);
        }

        if (!$class) {
            throw new RuntimeException('no driver is available in current system!');
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
     * @param array  $args
     *
     * @return ClientInterface
     */
    public static function __callStatic(string $method, array $args)
    {
        // if no default driver, create it.
        if (!$client = self::$defaultDriver) {
            $client = self::$defaultDriver = self::factory(self::$defaultConfig);
        }

        if (method_exists($client, $method)) {
            return $client->reset()->$method(...$args);
        }

        throw new InvalidArgumentException('call invalid class method: ' . $method);
    }
}
