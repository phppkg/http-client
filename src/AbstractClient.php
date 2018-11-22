<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/21
 * Time: 3:23 PM
 */

namespace PhpComp\Http\Client;

use Psr\Http\Message\ResponseInterface;

/**
 * Class AbstractClient
 * @package PhpComp\Http\Client
 */
abstract class AbstractClient implements ClientInterface
{
    /**
     * @var array
     */
    protected static $supportedMethods = [
        // method => allow post data(POST,PUT,PATCH)
        'POST' => true,
        'PUT' => true,
        'PATCH' => true,
        'GET' => false,
        'DELETE' => false,
        'HEAD' => false,
        'OPTIONS' => false,
        'TRACE' => false,
        'SEARCH' => false,
        'CONNECT' => false,
    ];

    /**
     * @var array Default options
     */
    protected $defaultOptions = [
        // open debug mode
        'debug' => false,
        // retry times, when an error occurred.
        'retry' => 3,
        'method' => 'GET', // 'POST'
        'baseUrl' => '',
        'timeout' => 10,

        'headers' => [
            // name => value
        ],
        'proxy' => [
            // 'host' => '',
            // 'port' => '',
        ],
        // send data
        'data' => [],
        'json' => [],
        // 'curlOptions' => [],
    ];

    /**
     * for create psr7 ResponseInterface instance
     * @var \Closure function(): ResponseInterface {..}
     */
    protected $responseCreator;

    /**
     * global options data. init from $defaultOptions
     * @var array
     */
    protected $options;

    /**
     * base Url
     * @var string
     */
    protected $baseUrl = '';

    /**
     * @param array $options
     * @return static
     * @throws \RuntimeException
     */
    public static function create(array $options = [])
    {
        return new static($options);
    }

    /**
     * SimpleCurl constructor.
     * @param array $options
     * @throws \RuntimeException
     */
    public function __construct(array $options = [])
    {
        if (!static::isAvailable()) {
            throw new \RuntimeException('The client driver' . static::class . ' is not available');
        }

        if (isset($options['baseUrl'])) {
            $this->setBaseUrl($options['baseUrl']);
            unset($options['baseUrl']);
        }

        $this->options = \array_merge($this->defaultOptions, $options);
    }

    /**
     * @return array
     */
    public static function getSupportedMethods()
    {
        return self::$supportedMethods;
    }

    /**************************************************************************
     * request methods
     *************************************************************************/

    /**
     * {@inheritDoc}
     */
    public function get(string $url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, self::GET, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function post(string $url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, self::POST, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, self::PUT, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function patch(string $url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, self::PATCH, $headers,  $options);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, self::DELETE, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function options(string $url, $data = null, array $headers = [], array $options = [])
    {
        return $this->request($url, $data, self::OPTIONS, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function head(string $url, $params = [], array $headers = [], array $options = [])
    {
        return $this->request($url, $params, self::HEAD, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function trace(string $url, $params = [], array $headers = [], array $options = [])
    {
        return $this->request($url, $params, self::TRACE, $headers, $options);
    }

    /**************************************************************************
     * extra methods
     *************************************************************************/

    /**
     * @return ResponseInterface
     */
    public function createPsr7Response(): ResponseInterface
    {
        return ($this->responseCreator)();
    }

    /**
     * @return \Closure
     */
    public function getResponseCreator(): \Closure
    {
        return $this->responseCreator;
    }

    /**
     * @param \Closure $responseCreator
     */
    public function setResponseCreator(\Closure $responseCreator): void
    {
        $this->responseCreator = $responseCreator;
    }

    /**
     * @return array
     */
    public function getCurlOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setCurlOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setBaseUrl(string $url)
    {
        $this->baseUrl = \trim($url);

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = \array_merge($this->options, $options);
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return (bool)$this->options['debug'];
    }

    /**
     * @param bool $debug
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->options['debug'] = (bool)$debug;

        return $this;
    }

    /**
     * @param int $retry
     * @return $this
     */
    public function setRetry(int $retry)
    {
        $this->options['retry'] = $retry;

        return $this;
    }

}
