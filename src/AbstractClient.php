<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\Client;

use Closure;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use stdClass;
use Toolkit\Stdlib\Str\UrlHelper;
use function array_merge;
use function base64_encode;
use function fclose;
use function fopen;
use function fwrite;
use function implode;
use function json_decode;
use function json_last_error;
use function ltrim;
use function str_replace;
use function strrpos;
use function strtolower;
use function substr;
use function trim;
use function ucwords;

/**
 * Class AbstractClient
 *
 * @package PhpPkg\Http\Client
 */
abstract class AbstractClient implements ClientInterface
{
    /**
     * for create psr7 ResponseInterface instance
     *
     * @var Closure function(): ResponseInterface {..}
     * @psalm-var Closure(): ResponseInterface
     */
    protected $responseCreator;

    /**
     * @var array Default options data
     */
    protected $defaultOptions = [
        // open debug mode
        'debug'     => false,
        // retry times, when an error occurred.
        'retry'     => 3,
        'method'    => 'GET', // 'POST'
        'baseUrl'   => '',
        'timeout'   => 5,
        // enable SSL verify
        'sslVerify' => false,
        // request headers
        // name => value
        'headers'   => [],
        // name => value
        'cookies'   => [],
        'proxy'     => [
            // 'host' => '',
            // 'port' => '',
        ],
        'auth'      => [
            // 'user' => '',
            // 'pwd' => '',
        ],
        'ssl'       => [
            // 'cert' => '',
            // ...
        ],
        // send data(todo)
        'data'      => [],
        'json'      => [],
        // extra
        // 一些针对不同驱动的自定义选项
        // 'curlOptions' => [],
        // 'coOptions' => [],
        // 'streamContextOptions' => [],
    ];

    /**
     * Global options data. init from $defaultOptions
     *
     * @see AbstractClient::$defaultOptions
     * @var array
     */
    protected $options;

    /**************************************************************************
     * request data.
     *************************************************************************/

    /**
     * base Url
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * setting headers for curl
     *
     * [ 'Content-Type' => 'Content-Type: application/json' ]
     *
     * @var array
     * @psalm-var array<string, string>
     */
    protected $headers = [];

    /**
     * @var array
     * @psalm-var array<string, string>
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
     * @var int response status code. eg. 200 404
     */
    protected $statusCode = 0;

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
     *
     * @return static
     * @throws RuntimeException
     */
    public static function new(array $options = []): ClientInterface
    {
        return new static($options);
    }

    /**
     * @param array $options
     *
     * @return static
     * @throws RuntimeException
     */
    public static function create(array $options = []): ClientInterface
    {
        return new static($options);
    }

    /**
     * @return string
     */
    public static function driverName(): string
    {
        $class = static::class;
        $name  = substr($class, strrpos($class, '\\') + 1);

        return strtolower(str_replace('Client', '', $name));
    }

    /**
     * SimpleCurl constructor.
     *
     * @param array $options
     *
     * @throws RuntimeException
     */
    public function __construct(array $options = [])
    {
        if (!static::isAvailable()) {
            throw new RuntimeException('The client driver' . static::class . ' is not available');
        }

        if (isset($options['baseUrl'])) {
            $this->setBaseUrl($options['baseUrl']);
            unset($options['baseUrl']);
        }

        $this->options = array_merge($this->defaultOptions, $options);
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
    public static function getSupportedMethods(): array
    {
        return self::SUPPORTED_METHODS;
    }

    /**************************************************************************
     * request methods
     *************************************************************************/

    /**
     * {@inheritDoc}
     */
    public function get(string $url, $params = null, array $headers = [], array $options = []): ClientInterface
    {
        return $this->request($url, $params, self::GET, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function post(string $url, $data = null, array $headers = [], array $options = []): ClientInterface
    {
        return $this->request($url, $data, self::POST, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $url, $data = null, array $headers = [], array $options = []): ClientInterface
    {
        return $this->request($url, $data, self::PUT, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function patch(string $url, $data = null, array $headers = [], array $options = []): ClientInterface
    {
        return $this->request($url, $data, self::PATCH, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $url, $params = null, array $headers = [], array $options = []): ClientInterface
    {
        return $this->request($url, $params, self::DELETE, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function options(string $url, $params = null, array $headers = [], array $options = []): ClientInterface
    {
        return $this->request($url, $params, self::OPTIONS, $headers, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function head(string $url, $params = null, array $headers = [], array $options = []): ClientInterface
    {
        return $this->request($url, $params, self::HEAD, $headers, $options);
    }

    /**
     * @param string $url
     * @param null $params
     * @param array $headers
     * @param array $options
     *
     * @return ClientInterface
     */
    public function trace(string $url, $params = null, array $headers = [], array $options = []): ClientInterface
    {
        return $this->request($url, $params, self::TRACE, $headers, $options);
    }

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        foreach ($request->getHeaders() as $name => $values) {
            $this->setHeader($name, implode(', ', $values));
        }

        // send request
        $this->request($request->getRequestTarget(), (string)$request->getBody(), $request->getMethod());

        return $this->getPsr7Response();
    }

    /**
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $options
     *
     * @return ClientInterface
     */
    public function json(string $url, $data = null, array $headers = [], array $options = []): ClientInterface
    {
        if (!isset($options['method'])) {
            $options['method'] = 'POST';
        }

        return $this->byJson()->request($url, $data, $options['method'], $headers, $options);
    }

    /**
     * File download and save
     *
     * @param string $url
     * @param string $saveAs
     *
     * @return bool
     */
    public function download(string $url, string $saveAs): bool
    {
        $data = $this->request($url)->getResponseBody();
        if ($this->isError()) {
            return false;
        }

        if (($fp = fopen($saveAs, 'wb')) === false) {
            throw new RuntimeException('Failed to open the save file', __LINE__);
        }

        fwrite($fp, $data);
        return fclose($fp);
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
     *
     * @return $this
     */
    public function setTimeout(int $seconds): self
    {
        $this->options['timeout'] = $seconds;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return $this
     */
    public function SSLVerify(bool $enable): self
    {
        $this->options['sslVerify'] = $enable;
        return $this;
    }

    /**************************************************************************
     * request cookies
     *************************************************************************/

    /**
     * Set contents of HTTP Cookie header.
     *
     * @param string $key The name of the cookie
     * @param string $value The value for the provided cookie name
     *
     * @return $this
     */
    public function setCookie(string $key, $value): ClientInterface
    {
        $this->cookies[$key] = (string)$value;
        return $this;
    }

    /**
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @param array $cookies
     *
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
     * accept Gzip
     */
    public function acceptGzip()
    {
        return $this->addHeaders([
            'Expect'          => '', // 首次速度非常慢 解决
            'Accept-Encoding' => 'gzip, deflate', // gzip
        ]);
    }

    /**
     * @return $this
     */
    public function withJsonType(): self
    {
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        return $this;
    }

    /**
     * @return $this
     */
    public function byJson(): self
    {
        return $this->withJsonType();
    }

    /**
     * @return $this
     */
    public function byXhr(): self
    {
        return $this->withAjax();
    }

    /**
     * @return $this
     */
    public function byAjax(): self
    {
        return $this->withAjax();
    }

    /**
     * @return $this
     */
    public function withAjax(): self
    {
        $this->setHeader('X-Requested-With', 'XMLHttpRequest');
        return $this;
    }

    /**
     * @param string $userAgent
     *
     * @return $this
     */
    public function setUserAgent(string $userAgent): ClientInterface
    {
        $this->setHeader('User-Agent', $userAgent);
        return $this;
    }

    /**
     * Use http auth
     *
     * @param string $user
     * @param string $pwd
     * @param int $authType CURLAUTH_BASIC CURLAUTH_DIGEST
     *
     * @return $this
     */
    public function setUserAuth(string $user, string $pwd = '', int $authType = self::AUTH_BASIC): AbstractClient
    {
        if ($authType === self::AUTH_BASIC) {
            $sign = 'Basic ' . base64_encode("$user:$pwd");
        } elseif ($authType === self::AUTH_DIGEST) {
            $sign = 'Digest ' . $user . $pwd;
        } else {
            throw new InvalidArgumentException('invalid auth type input');
        }

        $this->setHeader('Authorization', $sign);
        return $this;
    }

    /**
     * @param string $host
     * @param int $port
     *
     * @return $this
     */
    public function setProxy(string $host, int $port): ClientInterface
    {
        $this->options['proxy'] = [
            'host' => $host,
            'port' => $port,
        ];

        return $this;
    }

    /**
     * get Headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * set Headers
     *
     * @inheritdoc
     */
    public function setHeaders(array $headers): ClientInterface
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
     *
     * @return $this
     */
    public function addHeaders(array $headers, bool $override = true): ClientInterface
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
     *
     * @return $this
     */
    public function setHeader(string $name, string $value, bool $override = false): ClientInterface
    {
        $name = ucwords($name);

        if ($override || !isset($this->headers[$name])) {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * @param string|array $names
     *
     * @return $this
     */
    public function delHeader($names): ClientInterface
    {
        foreach ((array)$names as $name) {
            $name = ucwords($name);

            if (isset($this->headers[$name])) {
                unset($this->headers[$name]);
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
     *
     * @return string
     */
    protected function buildFullUrl(string $url, $data = null): string
    {
        $url = trim($url);

        // is a url part.
        if ($this->baseUrl && !UrlHelper::isFullURL($url)) {
            $url = $this->baseUrl . '/' . ltrim($url, '/');
        }

        // check again
        if (!UrlHelper::isFullURL($url)) {
            throw new RuntimeException("The request url is not full, URL $url");
        }

        if ($data) {
            return UrlHelper::build($url, $data);
        }

        return $url;
    }

    /**
     * create a empty Psr7 Response
     *
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
     *
     * @return $this
     */
    protected function resetOptions(): ClientInterface
    {
        $this->options = $this->defaultOptions;
        return $this;
    }

    /**
     * @return $this
     */
    public function resetRequest(): ClientInterface
    {
        $this->headers = [];
        $this->cookies = [];
        return $this;
    }

    /**
     * @return $this
     */
    public function resetHeaders(): ClientInterface
    {
        $this->headers = [];
        return $this;
    }

    /**
     * @return $this
     */
    public function resetCookies(): ClientInterface
    {
        $this->cookies = [];
        return $this;
    }

    /**
     * @return $this
     */
    public function resetResponse(): ClientInterface
    {
        $this->responseBody    = '';
        $this->responseHeaders = [];
        return $this;
    }

    /**
     * Reset the last time headers,cookies,options,response data.
     *
     * @return $this
     */
    public function reset(): ClientInterface
    {
        $this->resetOptions();

        return $this->resetRequest()->resetResponse();
    }

    /**************************************************************************
     * getter/setter methods
     *************************************************************************/

    /**
     * @return Closure
     */
    public function getResponseCreator(): Closure
    {
        return $this->responseCreator;
    }

    /**
     * @param Closure $responseCreator
     *
     * @return AbstractClient
     */
    public function setResponseCreator(Closure $responseCreator): ClientInterface
    {
        $this->responseCreator = $responseCreator;
        return $this;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setBaseUrl(string $url): ClientInterface
    {
        $this->baseUrl = trim($url);
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
     * @param null|mixed $default
     *
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
     *
     * @return ClientInterface
     */
    public function setOptions(array $options): ClientInterface
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return ClientInterface
     */
    public function setOption(string $key, $value): ClientInterface
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return (bool)$this->options['debug'];
    }

    /**
     * @param bool|mixed $debug
     *
     * @return $this
     */
    public function setDebug($debug): ClientInterface
    {
        $this->options['debug'] = (bool)$debug;
        return $this;
    }

    /**
     * @param int $retry
     *
     * @return $this
     */
    public function setRetry(int $retry): ClientInterface
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
     * @return array
     */
    public function getArrayData(): array
    {
        return $this->getJsonArray();
    }

    /**
     * @return array
     */
    public function getJsonArray(): array
    {
        if (!$body = $this->getResponseBody()) {
            return [];
        }

        $data = json_decode($body, true);
        if (json_last_error() > 0) {
            return [];
        }

        return $data;
    }

    /**
     * @return bool|stdClass
     */
    public function getJsonObject()
    {
        if (!$body = $this->getResponseBody()) {
            return false;
        }

        $data = json_decode($body, false);
        if (json_last_error() > 0) {
            return false;
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->getResponseBody();
    }

    /**
     * @return string
     */
    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    /**
     * @return array
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * @param string $name
     * @param null $default
     *
     * @return string
     */
    public function getResponseHeader(string $name, $default = null): ?string
    {
        $name = ucwords($name);
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
     *
     * @return bool
     */
    public function isError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 600;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getDriverName(): string
    {
        return self::driverName();
    }
}
