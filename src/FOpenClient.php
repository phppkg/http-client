<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-23
 * Time: 19:31
 */

namespace PhpComp\Http\Client;

use PhpComp\Http\Client\Error\ClientException;
use PhpComp\Http\Client\Traits\RawResponseParserTrait;
use PhpComp\Http\Client\Traits\StreamContextBuildTrait;

/**
 * Class FOpenClient - powered by func fopen()
 * @package PhpComp\Http\Client
 */
class FOpenClient extends AbstractClient
{
    use RawResponseParserTrait, StreamContextBuildTrait;

    /**
     * The network resource handle, it's created by:
     * fopen()
     * fsockopen()
     * stream_socket_client()
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
        return \function_exists('fopen');
    }

    /**
     * Send request to remote URL
     * @param $url
     * @param array $data
     * @param string $method
     * @param array $headers
     * @param array $options
     * @return self
     */
    public function request(string $url, $data = null, string $method = self::GET, array $headers = [], array $options = [])
    {
        if ($method) {
            $options['method'] = \strtoupper($method);
        }

        // get request url info
        $url = $this->buildUrl($url);
        // merge global options data.
        $options = \array_merge($this->options, $options);

        try {
            $ctx = $this->buildStreamContext($url, $headers, $options, $data);
            $fullUrl = ClientUtil::encodeURL($this->fullUrl);
            // send request
            $this->handle = \fopen($fullUrl, 'rb', false, $ctx);
        } catch (\Throwable $e) {
            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }

        // set timeout
        \stream_set_timeout($this->handle, $this->getTimeout());

        // read response
        // $content = \stream_get_contents($this->handle);
        while (!\feof($this->handle)) {
            $this->responseBody .= \fread($this->handle, 4096);
        }

        // don't need parse
        $this->setResponseParsed(true);

        // save some info
        $this->responseInfo = \stream_get_meta_data($this->handle);

        if (isset($this->responseInfo['wrapper_data'])) {
            $rawHeaders = $this->responseInfo['wrapper_data'];
            $this->parseResponseHeaders($rawHeaders);
            unset($this->responseInfo['wrapper_data']);
        }

        \fclose($this->handle);
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