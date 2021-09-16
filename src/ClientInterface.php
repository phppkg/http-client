<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client;

use Closure;

/**
 * Interface ClientInterface
 *
 * @package PhpComp\Http\Client
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
     * @return ClientInterface
     */
    public static function create(array $options): ClientInterface;

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
     * @return ClientInterface
     */
    public function get(string $url, $params = null, array $headers = [], array $options = []): ClientInterface;

    /**
     * POST
     *
     * @param string $url
     * @param mixed  $data
     * @param array  $headers
     * @param array  $options
     *
     * @return ClientInterface
     */
    public function post(string $url, $data = null, array $headers = [], array $options = []): ClientInterface;

    /**
     * PUT
     *
     * @param string $url
     * @param mixed  $data
     * @param array  $headers
     * @param array  $options
     *
     * @return ClientInterface
     */
    public function put(string $url, $data = null, array $headers = [], array $options = []): ClientInterface;

    /**
     * PATCH
     *
     * @param string $url
     * @param mixed  $data
     * @param array  $headers
     * @param array  $options
     *
     * @return ClientInterface
     */
    public function patch(string $url, $data = null, array $headers = [], array $options = []): ClientInterface;

    /**
     * DELETE
     *
     * @param string $url
     * @param null   $params
     * @param array  $headers
     * @param array  $options
     *
     * @return ClientInterface
     */
    public function delete(string $url, $params = null, array $headers = [], array $options = []): ClientInterface;

    /**
     * OPTIONS
     *
     * @param string $url
     * @param null   $params
     * @param array  $headers
     * @param array  $options
     *
     * @return ClientInterface
     */
    public function options(string $url, $params = null, array $headers = [], array $options = []): ClientInterface;

    /**
     * HEAD
     *
     * @param string $url
     * @param array  $params
     * @param array  $headers
     * @param array  $options
     *
     * @return ClientInterface
     */
    public function head(string $url, array $params = [], array $headers = [], array $options = []): ClientInterface;

    /**
     * TRACE
     *
     * @param string $url
     * @param array  $params
     * @param array  $headers
     * @param array  $options
     *
     * @return ClientInterface
     */
    public function trace(string $url, array $params = [], array $headers = [], array $options = []): ClientInterface;

    /**
     * Send request to remote URL
     *
     * @param string            $url
     * @param array|string|null $data
     * @param string            $method
     * @param array             $headers
     * @param array             $options All options please {@see AbstractClient::$defaultOptions}
     *
     * @return ClientInterface
     */
    public function request(
        string $url,
        $data = null,
        string $method = '',
        array $headers = [],
        array $options = []
    ): ClientInterface;

    /**
     * @param Closure $responseCreator
     *
     * @return self
     */
    public function setResponseCreator(Closure $responseCreator): ClientInterface;

    /**
     * reset options, request headers, cookies, response data...
     *
     * @return self
     */
    public function reset(): ClientInterface;

    /**
     * Reset request data
     *
     * @return self
     */
    public function resetRequest(): ClientInterface;

    /**
     * Reset response data
     *
     * @return self
     */
    public function resetResponse(): ClientInterface;

    /**************************************************************************
     * config client
     *************************************************************************/

    /**
     * @param string $host
     * @param int    $port
     *
     * @return $this
     */
    public function setProxy(string $host, int $port): ClientInterface;

    /**
     * @param string $userAgent
     *
     * @return $this
     */
    public function setUserAgent(string $userAgent): ClientInterface;

    /**
     * @param array $options
     *
     * @return ClientInterface
     */
    public function setOptions(array $options): ClientInterface;

    /**
     * @param string $key
     * @param $value
     *
     * @return ClientInterface
     */
    public function setOption(string $key, $value): ClientInterface;

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
    public function setHeaders(array $headers): self;

    /**
     * add headers
     *
     * @param array $headers
     * @param bool  $override $override Override exists
     *
     * @return mixed
     */
    public function addHeaders(array $headers, bool $override = true): self;

    /**
     * @param string|array $names
     *
     * @return $this
     */
    public function delHeader($names): self;

    /**
     * @return mixed
     */
    public function getHeaders(): array;

    /**************************************************************************
     * request cookies
     *************************************************************************/

    /**
     * @param string $key
     * @param        $value
     *
     * @return self
     */
    public function setCookie(string $key, $value): self;

    /**************************************************************************
     * response info
     *************************************************************************/

    /**
     * Was an 'info' header returned.
     */
    public function isInfo(): bool;

    /**
     * Was an 'OK' response returned.
     */
    public function isSuccess(): bool;

    /**
     * Was a 'redirect' returned.
     */
    public function isRedirect(): bool;

    /**
     * Was an 'error' returned (client error or server error).
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
