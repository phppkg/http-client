<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client;

use PhpComp\Http\Client\Error\ClientException;
use PhpComp\Http\Client\Error\RequestException;
use PhpComp\Http\Client\Traits\BuildRawHttpRequestTrait;
use PhpComp\Http\Client\Traits\ParseRawResponseTrait;
use InvalidArgumentException;
use function function_exists;
use function is_resource;
use function strtoupper;
use function array_merge;
use function sprintf;
use function stream_socket_client;
use function stream_set_timeout;
use function fwrite;
use function stream_get_meta_data;
use function feof;
use function fread;
use function fclose;
use const STREAM_CLIENT_CONNECT;
use const STREAM_CLIENT_PERSISTENT;

/**
 * Class StreamClient
 * @package PhpComp\Http\Client
 */
class StreamClient extends AbstractClient
{
    use BuildRawHttpRequestTrait, ParseRawResponseTrait;

    /**
     * @see https://secure.php.net/manual/zh/function.stream-get-meta-data.php
     * @var array get from \stream_get_meta_data()
     * data like:
     * [
     *  'timed_out' => bool(false)
     *  'blocked' => bool(true)
     *  'eof' => bool(true)
     *  'wrapper_type' => string(4) "http"
     *  'stream_type' => string(14) "tcp_socket/ssl"
     *  'mode' => string(2) "rb"
     *  'unread_bytes' => int(0)
     *  'seekable' => bool(false)
     * ]
     */
    private $responseInfo = [];

    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return function_exists('stream_socket_client');
    }

    /**
     * stream context. it's create by stream_context_create()
     * @param array $opts
     * @return mixed|resource
     */
    protected function buildStreamContext(array $opts)
    {
        if (isset($opts['streamContext'])) {
            $context = $opts['streamContext'];

            // Suppress the error since we'll catch it below
            if (is_resource($context) && get_resource_type($context) !== 'stream-context') {
                throw new InvalidArgumentException("Stream context in options[streamContext] isn't a valid context resource");
            }
        } else {
            $context = StreamContext::create();
        }

        $method = $this->formatAndCheckMethod($opts['method']);
        $httpOptions = [
            'method' => $method,
            'timeout' => (int)$opts['timeout'],
            // 'content' => $body,
        ];

        // 设置代理
        if ($proxy = $opts['proxy']) {
            $httpOptions['proxy'] = sprintf('tcp://%s:%d', $proxy['host'], (int)$proxy['port']);
        }

        StreamContext::setHTTPOptions($context, $httpOptions);

        // user can custom set context options.
        // please refer StreamContext::createXXOptions()
        if (isset($opts['streamContextOptions'])) {
            StreamContext::setOptions($context, $opts['streamContextOptions']);
        }

        return $context;
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
            $options['method'] = strtoupper($method);
        }

        // merge global options data.
        $options = array_merge($this->options, $options);

        // get request url info
        $info = ClientUtil::parseUrl($this->buildFullUrl($url));
        $ctx = $this->buildStreamContext($options);

        $timeout = (int)$options['timeout'];
        $socketUrl = sprintf('tcp://%s:%d', $info['host'], (int)$info['port']);
        $flags = STREAM_CLIENT_CONNECT;

        if (isset($options['persistent']) && $options['persistent']) {
            $flags = STREAM_CLIENT_PERSISTENT;
        }

        /*
         * create stream socket client
         * flags: STREAM_CLIENT_CONNECT (default), STREAM_CLIENT_ASYNC_CONNECT and STREAM_CLIENT_PERSISTENT.
         */
        $handle = stream_socket_client($socketUrl, $errno, $error, $timeout, $flags, $ctx);

        // if create failure
        if (!$handle) {
            throw new ClientException($error, $errno);
        }

        $string = $this->buildRawHttpData($info, $headers, $options, $data);

        // set timeout
        stream_set_timeout($handle, $timeout);

        // send request
        if (false === fwrite($handle, $string)) {
            throw new RequestException('send request to server is failure');
        }

        // save some info
        $this->responseInfo = stream_get_meta_data($handle);

        // read response
        while (!feof($handle)) {
            $this->rawResponse .= fread($handle, 4096);
        }

        fclose($handle);

        // parse raw response
        $this->parseResponse();
        return $this;
    }

    /**
     * @return $this
     */
    public function resetResponse()
    {
        $this->rawResponse = '';
        $this->responseParsed = false;

        parent::resetResponse();
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
