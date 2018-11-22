<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/21
 * Time: 3:23 PM
 */

namespace PhpComp\Http\Client;

use Psr\Http\Message\RequestInterface;
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
        // enable SSL verify
        'sslVerify' => false,

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

    /**************************************************************************
     * request data.
     *************************************************************************/

    /**
     * base Url
     * @var string
     */
    protected $baseUrl = '';

    /**
     * setting headers for curl
     *
     * [ 'Content-Type' => 'Content-Type: application/json' ]
     *
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $cookies = [];

    /**************************************************************************
     * response data
     *************************************************************************/

    /**
     * @var int
     */
    protected $errNo = 0;

    /**
     * @var string
     */
    protected $error = '';

    /**
     * @var int response status code
     */
    protected $statusCode = 200;

    /**
     * @var string body string, it's parsed from $_response
     */
    protected $responseBody = '';

    /**
     * @var string[] headers data, it's parsed from $_response
     */
    protected $responseHeaders = [];

    /**
     * @param array $options
     * @return static
     * @throws \RuntimeException
     */
    public static function create(array $options = []): ClientInterface
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
     * __destruct
     */
    public function __destruct()
    {
        $this->reset();
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
        return $this->request($url, $data, self::PATCH, $headers, $options);
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

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        foreach ($request->getHeaders() as $name => $values) {
            $this->setHeader($name, \implode(', ', $values));
        }

        // send request
        $this->request($request->getRequestTarget(), $request->getBody(), $request->getMethod());

        return $this->getPsr7Response();
    }

    /**************************************************************************
     * config client
     *************************************************************************/

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return (int)$this->options['timeout'];
    }

    /**
     * @param int $seconds
     * @return $this
     */
    public function setTimeout(int $seconds)
    {
        $this->options['timeout'] = $seconds;
        return $this;
    }

    /**
     * @param bool $enable
     * @return $this
     */
    public function SSLVerify(bool $enable)
    {
        $this->options['sslVerify'] = $enable;
        return $this;
    }

    /**************************************************************************
     * request cookies
     *************************************************************************/

    /**
     * Set contents of HTTP Cookie header.
     * @param string $key The name of the cookie
     * @param string $value The value for the provided cookie name
     * @return $this
     */
    public function setCookie($key, $value)
    {
        $this->cookies[$key] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @param array $cookies
     * @return AbstractClient
     */
    public function setCookies(array $cookies): AbstractClient
    {
        $this->cookies = $cookies;
        return $this;
    }

    /**************************************************************************
     * request headers
     *************************************************************************/

    /**
     * @return $this
     */
    public function byJson()
    {
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');

        return $this;
    }

    /**
     * @return $this
     */
    public function byXhr()
    {
        return $this->byAjax();
    }

    /**
     * @return $this
     */
    public function byAjax()
    {
        $this->setHeader('X-Requested-With', 'XMLHttpRequest');

        return $this;
    }

    /**
     * get Headers
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return array
     */
    public function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $name => $value) {
            $name = \ucwords($name);
            $formatted[] = "$name: $value";
        }

        return $formatted;
    }

    /**
     * set Headers
     * @inheritdoc
     */
    public function setHeaders(array $headers)
    {
        $this->headers = []; // clear old.

        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value, true);
        }

        return $this;
    }

    /**
     * @param array $headers
     * @param bool $override
     * @return $this
     */
    public function addHeaders(array $headers, bool $override = true)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value, $override);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool $override
     * @return $this
     */
    public function setHeader(string $name, string $value, bool $override = false)
    {
        if ($override || !isset($this->headers[$name])) {
            $this->headers[$name] = \ucwords($name) . ": $value";
        }

        return $this;
    }

    /**
     * @param string|array $names
     * @return $this
     */
    public function delHeader($names)
    {
        foreach ((array)$names as $item) {
            if (isset($this->headers[$item])) {
                unset($this->headers[$item]);
            }
        }

        return $this;
    }

    /**************************************************************************
     * extra methods
     *************************************************************************/

    /**
     * @param string $url
     * @param mixed $data
     * @return string
     */
    protected function buildUrl(string $url, $data = null)
    {
        $url = \trim($url);

        // is a url part.
        if ($this->baseUrl && !ClientUtil::isFullURL($url)) {
            $url = $this->baseUrl . $url;
        }

        // check again
        if (!ClientUtil::isFullURL($url)) {
            throw new \RuntimeException("The request url is not full, URL $url");
        }

        if ($data) {
            return ClientUtil::buildURL($url, $data);
        }

        return $url;
    }

    /**
     * create a empty Psr7 Response
     * @return ResponseInterface
     */
    public function createPsr7Response(): ResponseInterface
    {
        return ($this->responseCreator)();
    }

    /**
     * @return ResponseInterface
     */
    public function getPsr7Response(): ResponseInterface
    {
        // create response instance.
        $psr7res = $this->createPsr7Response();

        // write body data
        $psr7res->getBody()->write($this->getResponseBody());

        // with status
        $psr7res = $psr7res->withStatus($this->getStatusCode());

        // add headers
        foreach ($this->getResponseHeaders() as $name => $value) {
            $psr7res = $psr7res->withHeader($name, $value);
        }

        return $psr7res;
    }

    /**************************************************************************
     * reset data/unset attribute
     *************************************************************************/

    /**
     * reset Options
     * @return $this
     */
    protected function resetOptions()
    {
        $this->options = $this->defaultOptions;
        return $this;
    }

    /**
     * @return $this
     */
    public function resetRequest()
    {
        $this->headers = [];
        $this->cookies = [];
        return $this;
    }

    /**
     * @return $this
     */
    public function resetHeaders()
    {
        $this->headers = [];
        return $this;
    }

    /**
     * @return $this
     */
    public function resetCookies()
    {
        $this->cookies = [];
        return $this;
    }

    /**
     * @return $this
     */
    public function resetResponse()
    {
        $this->responseBody = '';
        $this->responseHeaders = [];
        return $this;
    }

    /**
     * Reset the last time headers,cookies,options,response data.
     * @return $this
     */
    public function reset()
    {
        $this->resetOptions();

        return $this->resetRequest()->resetResponse();
    }

    /**************************************************************************
     * getter/setter methods
     *************************************************************************/

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
     * @param int|string $name
     * @param bool $default
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return $this->options[$name] ?? $default;
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getResponseBody();
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->getResponseBody();
    }

    /**
     * @return string
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * @param string $name
     * @param null $default
     * @return string
     */
    public function getResponseHeader($name, $default = null)
    {
        return $this->responseHeaders[$name] ?? $default;
    }

    /**
     * Was an 'info' header returned.
     */
    public function isInfo(): bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Was an 'OK' response returned.
     */
    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Was a 'redirect' returned.
     */
    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Was an 'error' returned (client error or server error).
     */
    public function isError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 600;
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return (bool)$this->error;
    }

    /**
     * @return int
     */
    public function getErrNo(): int
    {
        return $this->errNo;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

}
