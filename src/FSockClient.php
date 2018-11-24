<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-30
 * Time: 17:26
 */

namespace PhpComp\Http\Client;

use PhpComp\Http\Client\Error\ClientException;
use PhpComp\Http\Client\Error\RequestException;
use PhpComp\Http\Client\Traits\BuildRawHttpRequestTrait;
use PhpComp\Http\Client\Traits\RawResponseParserTrait;

/**
 * Class FSockClient - powered by func fsockopen()
 * @package PhpComp\Http\Client
 */
class FSockClient extends AbstractClient
{
    use BuildRawHttpRequestTrait, RawResponseParserTrait;

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
        return \function_exists('fsockopen');
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
        $info = ClientUtil::parseUrl($this->buildUrl($url));

        // merge global options data.
        $options = \array_merge($this->options, $options);
        $timeout = (int)$options['timeout'];

        // open socket connection
        if (isset($options['persistent']) && $options['persistent']) {
            $handle = \pfsockopen($info['host'], $info['port'], $errno, $error, $timeout);
        } else {
            $handle = \fsockopen($info['host'], $info['port'], $errno, $error, $timeout);
        }

        // if open fail
        if (!$handle) {
            throw new ClientException($error, $errno);
        }

        $string = $this->buildRawHttpData($info, $headers, $options, $data);

        // set timeout
        \stream_set_timeout($handle, $timeout);

        // send request
        if (false === \fwrite($handle, $string)) {
            throw new RequestException('send request to server is fail');
        }

        // read response
        while (!\feof($handle)) {
            $this->rawResponse .= \fread($handle, 4096);
        }

        // save some info
        $this->responseInfo = \stream_get_meta_data($handle);

        \fclose($handle);

        // parse raw response
        $this->parseResponse();

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
