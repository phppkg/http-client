<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\Client;

/**
 * Interface ClientInterface
 *
 * @package PhpPkg\Http\Client
 */
interface ClientInterface extends \Psr\Http\Client\ClientInterface
{
    // http auth
    public const AUTH_BASIC = 1;

    public const AUTH_DIGEST = 2;

    // request method list
    public const GET = 'GET';

    public const POST = 'POST';

    public const PUT = 'PUT';

    public const PATCH = 'PATCH';

    public const DELETE = 'DELETE';

    public const HEAD = 'HEAD';

    public const OPTIONS = 'OPTIONS';

    public const TRACE = 'TRACE';

    public const SEARCH = 'SEARCH';

    /**
     * @var array
     */
    public const SUPPORTED_METHODS = [
        // method => allow post data(POST,PUT,PATCH)
        'POST'    => true,
        'PUT'     => true,
        'PATCH'   => true,
        'GET'     => false,
        'DELETE'  => false,
        'HEAD'    => false,
        'OPTIONS' => false,
        'TRACE'   => false,
        'SEARCH'  => false,
        'CONNECT' => false,
    ];

    /**
     * @param array $options
     *
     * @return static
     */
    public static function new(array $options): static;

    /**
     * @param array $options
     *
     * @return static
     */
    public static function create(array $options): static;

    /**
     * @return string
     */
    public static function driverName(): string;

    /**
     * @return bool
     */
    public static function isAvailable(): bool;

    /**
     * @return string
     */
    public function __toString(): string;

    /**
     * GET
     *
     * @param string $url
     * @param null   $params
     * @param array  $headers
     * @param array  $options
     *
     * @return static
     */
    public function get(string $url, $params = null, array $headers = [], array $options = []): static;

    /**
     * POST
     *
     * @param string $url
     * @param mixed|null $data
     * @param array  $headers
     * @param array  $options
     *
     * @return static
     */
    public function post(string $url, mixed $data = null, array $headers = [], array $options = []): static;

    /**
     * PUT
     *
     * @param string $url
     * @param mixed|null $data
     * @param array  $headers
     * @param array  $options
     *
     * @return static
     */
    public function put(string $url, mixed $data = null, array $headers = [], array $options = []): static;

    /**
     * PATCH
     *
     * @param string $url
     * @param mixed|null $data
     * @param array  $headers
     * @param array  $options
     *
     * @return static
     */
    public function patch(string $url, mixed $data = null, array $headers = [], array $options = []): static;

    /**
     * DELETE
     *
     * @param string $url
     * @param null   $params
     * @param array  $headers
     * @param array  $options
     *
     * @return static
     */
    public function delete(string $url, $params = null, array $headers = [], array $options = []): static;

    /**
     * OPTIONS
     *
     * @param string $url
     * @param null   $params
     * @param array  $headers
     * @param array  $options
     *
     * @return static
     */
    public function options(string $url, $params = null, array $headers = [], array $options = []): static;

    /**
     * HEAD
     *
     * @param string $url
     * @param array  $params
     * @param array  $headers
     * @param array  $options
     *
     * @return static
     */
    public function head(string $url, array $params = [], array $headers = [], array $options = []): static;

    /**
     * TRACE
     *
     * @param string $url
     * @param array  $params
     * @param array  $headers
     * @param array  $options
     *
     * @return static
     */
    public function trace(string $url, array $params = [], array $headers = [], array $options = []): static;

    /**
     * Send request to remote URL
     *
     * @param string            $url
     * @param array|string|null $data
     * @param string            $method
     * @param array             $headers
     * @param array             $options All options please {@see AbstractClient::$defaultOptions}
     *
     * @return static
     */
    public function request(
        string $url,
        array|string $data = null,
        string $method = '',
        array $headers = [],
        array $options = []
    ): static;

    /**
     * @param callable $responseCreator
     *
     * @return self
     */
    public function setResponseCreator(callable $responseCreator): static;

    /**
     * reset options, request headers, cookies, response data...
     *
     * @return self
     */
    public function reset(): static;

    /**
     * Reset request data
     *
     * @return self
     */
    public function resetRequest(): static;

    /**
     * Reset response data
     *
     * @return self
     */
    public function resetResponse(): static;

    /**************************************************************************
     * config client
     *************************************************************************/

    /**
     * @param string $host
     * @param int    $port
     *
     * @return $this
     */
    public function setProxy(string $host, int $port): static;

    /**
     * @param string $userAgent
     *
     * @return $this
     */
    public function setUserAgent(string $userAgent): static;

    /**
     * @param array $options
     *
     * @return static
     */
    public function setOptions(array $options): static;

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return static
     */
    public function setOption(string $key, mixed $value): static;

    /**
     * @return bool
     */
    public function isDebug(): bool;

    /**
     * @param bool|mixed $debug
     *
     * @return $this
     */
    public function setDebug(mixed $debug): static;

    /**************************************************************************
     * request cookies
     *************************************************************************/

    /**
     * set Headers
     *
     * [
     *  'Content-Type' => 'application/json'
     * ]
     *
     * @param array $headers
     *
     * @return $this
     */
    public function setHeaders(array $headers): static;

    /**
     * add headers
     *
     * @param array $headers
     * @param bool  $override $override Override exists
     *
     * @return mixed
     */
    public function addHeaders(array $headers, bool $override = true): static;

    /**
     * @param array|string $names
     *
     * @return $this
     */
    public function delHeader(array|string $names): static;

    /**
     * @return mixed
     */
    public function getHeaders(): array;

    /**************************************************************************
     * request cookies
     *************************************************************************/

    /**
     * @param string $key
     * @param int|string $value
     *
     * @return self
     */
    public function setCookie(string $key, int|string $value): self;

    /**************************************************************************
     * response info
     *************************************************************************/

    /**
     * Was an 'info' header returned.
     *
     * @return bool
     */
    public function isInfo(): bool;

    /**
     * Was an 'OK' response returned.
     *
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * Was a 'redirect' returned.
     *
     * @return bool
     */
    public function isRedirect(): bool;

    /**
     * Was an 'error' returned (client error or server error).
     *
     * @return bool
     */
    public function isError(): bool;

    /**
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * @return array
     */
    public function getResponseHeaders(): array;

    /**
     * @return string
     */
    public function getResponseBody(): string;

    /**************************************************************************
     * getter/setter
     *************************************************************************/

    /**
     * get current driver name
     * return like curl, stream, fsock, fopen, file, co, co2
     *
     * @return string
     */
    public function getDriverName(): string;
}
