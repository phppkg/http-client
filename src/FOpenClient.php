<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\Client;

use PhpPkg\Http\Client\Exception\ClientException;
use PhpPkg\Http\Client\Traits\ParseRawResponseTrait;
use PhpPkg\Http\Client\Traits\StreamContextBuildTrait;
use Throwable;
use Toolkit\Stdlib\Str\UrlHelper;
use function array_merge;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function function_exists;
use function stream_get_meta_data;
use function stream_set_timeout;
use function strtoupper;

/**
 * Class FOpenClient - powered by func fopen()
 *
 * @package PhpPkg\Http\Client
 */
class FOpenClient extends AbstractClient
{
    use ParseRawResponseTrait, StreamContextBuildTrait;

    /**
     * The network resource handle, it's created by:
     * fopen()
     * fsockopen()
     * stream_socket_client()
     *
     * @var resource
     */
    protected $handle;

    /**
     * @see https://secure.php.net/manual/zh/function.stream-get-meta-data.php
     * @var array get from \stream_get_meta_data()
     * data like:
     * [
     *  'timed_out' => bool(false)
     *  'blocked' => bool(true)
     *  'eof' => bool(true)
     *  'wrapper_data' =>  [ // headers data], // will remove and parse to $responseHeaders
     *  'wrapper_type' => string(4) "http"
     *  'stream_type' => string(14) "tcp_socket/ssl"
     *  'mode' => string(2) "rb"
     *  'unread_bytes' => int(0)
     *  'seekable' => bool(false)
     *  'uri' => string(20) "http://www.baidu.com"
     * ]
     */
    private $responseInfo = [];

    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return function_exists('fopen');
    }

    /**
     * Send request to remote URL
     *
     * @param string $url
     * @param null   $data
     * @param string $method
     * @param array  $headers
     * @param array  $options
     *
     * @return self
     */
    public function request(
        string $url,
        $data = null,
        string $method = self::GET,
        array $headers = [],
        array $options = []
    ): ClientInterface {
        if ($method) {
            $options['method'] = strtoupper($method);
        }

        // get request url info
        $url = $this->buildFullUrl($url);
        // merge global options data.
        $options = array_merge($this->options, $options);

        try {
            $ctx = $this->buildStreamContext($url, $headers, $options, $data);

            $fullUrl = UrlHelper::encode2($this->fullUrl);
            // send request
            $this->handle = fopen($fullUrl, 'rb', false, $ctx);
        } catch (Throwable $e) {
            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }

        // set timeout
        stream_set_timeout($this->handle, (int)$options['timeout']);

        // read response
        // $content = \stream_get_contents($this->handle);
        while (!feof($this->handle)) {
            $this->responseBody .= fread($this->handle, 4096);
        }

        // save some info
        $this->responseInfo = stream_get_meta_data($this->handle);

        // collect headers data
        if (isset($this->responseInfo['wrapper_data'])) {
            $rawHeaders = $this->responseInfo['wrapper_data'];
            $this->parseResponseHeaders($rawHeaders);
            unset($this->responseInfo['wrapper_data']);
        }

        fclose($this->handle);
        return $this;
    }

    /**
     * @return array
     */
    public function getResponseInfo(): array
    {
        return $this->responseInfo;
    }
}
