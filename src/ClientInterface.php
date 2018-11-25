<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/21
 * Time: 3:35 PM
 */

namespace PhpComp\Http\Client;

/**
 * Interface ClientInterface
 * @package PhpComp\Http\Client
 */
interface ClientInterface extends \Psr\Http\Client\ClientInterface
{
    // http auth
    const AUTH_BASIC = 1;
    const AUTH_DIGEST = 2;

    // request method list
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';
    const HEAD = 'HEAD';
    const OPTIONS = 'OPTIONS';
    const TRACE = 'TRACE';
    const SEARCH = 'SEARCH';

    /**
     * @param array $options
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
     * {@inheritdoc}
     */
    public function get(string $url, $params = null, array $headers = [], array $options = []);

    /**
     * POST
     * {@inheritdoc}
     */
    public function post(string $url, $data = null, array $headers = [], array $options = []);

    /**
     * PUT
     * {@inheritdoc}
     */
    public function put(string $url, $data = null, array $headers = [], array $options = []);

    /**
     * PATCH
     * {@inheritdoc}
     */
    public function patch(string $url, $data = null, array $headers = [], array $options = []);

    /**
     * DELETE
     * {@inheritdoc}
     */
    public function delete(string $url, $params = null, array $headers = [], array $options = []);

    /**
     * OPTIONS
     * {@inheritdoc}
     */
    public function options(string $url, $params = null, array $headers = [], array $options = []);

    /**
     * HEAD
     * {@inheritdoc}
     */
    public function head(string $url, $params = null, array $headers = [], array $options = []);

    /**
     * TRACE
     * {@inheritdoc}
     */
    public function trace(string $url, $params = null, array $headers = [], array $options = []);

    /**
     * Send request to remote URL
     * @param $url
     * @param array $data
     * @param string $method
     * @param array $headers
     * @param array $options
     * @return self
     */
    public function request(string $url, $data = null, string $method = self::GET, array $headers = [], array $options = []);

    /**
     * @param \Closure $responseCreator
     * @return self
     */
    public function setResponseCreator(\Closure $responseCreator);

    /**
     * reset options, request headers, cookies, response data...
     * @return self
     */
    public function reset();

    /**
     * Reset request data
     * @return self
     */
    public function resetRequest();

    /**
     * Reset response data
     * @return self
     */
    public function resetResponse();

    /**************************************************************************
     * config client
     *************************************************************************/

    /**
     * @param string $host
     * @param int $port
     * @return $this
     */
    public function setProxy(string $host, int $port);

    /**
     * @param string $userAgent
     * @return $this
     */
    public function setUserAgent(string $userAgent);

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
     * @return $this
     */
    public function setHeaders(array $headers);

    /**
     * add headers
     * @param array $headers
     * @param bool $override $override Override exists
     * @return mixed
     */
    public function addHeaders(array $headers, bool $override = true);

    /**
     * @param string|array $names
     * @return $this
     */
    public function delHeader($names);

    /**
     * @return mixed
     */
    public function getHeaders(): array;

    /**************************************************************************
     * request cookies
     *************************************************************************/

    /**
     * @param string $key
     * @param $value
     * @return self
     */
    public function setCookie(string $key, $value);

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

    public function getStatusCode(): int;

    public function getResponseHeaders(): array;

    public function getResponseBody(): string;

    /**************************************************************************
     * getter/setter
     *************************************************************************/

    /**
     * get current driver name
     * return like curl, stream, fsock, fopen, file, co, co2
     * @return string
     */
    public function getDriverName(): string;


}
