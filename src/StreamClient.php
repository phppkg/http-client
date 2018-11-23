<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/9/11
 * Time: 下午8:04
 */

namespace PhpComp\Http\Client;

use PhpComp\Http\Client\Traits\BuildRawHttpRequestTrait;
use PhpComp\Http\Client\Traits\RawResponseParserTrait;

/**
 * Class StreamClient
 * @package PhpComp\Http\Client
 */
class StreamClient extends AbstractClient
{
    use BuildRawHttpRequestTrait, RawResponseParserTrait;

    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return \function_exists('stream_socket_client');
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
        $timeout = $this->getTimeout();
        $socketUrl = \sprintf('tcp://%s:%d', $info['host'], (int)$info['port']);

        // open socket connection
        $fp = \stream_socket_client($socketUrl, $errno, $error, $timeout);

        // save error info
        if (!$fp) {
            $this->errNo = $errno;
            $this->error = $error;
            return $this;
        }

        // set timeout
        \stream_set_timeout($fp, $timeout);

        // merge global options data.
        $options = \array_merge($this->options, $options);
        $string = $this->buildHttpData($info, $headers, $options, $data);
        \fwrite($fp, $string); // send request

        // read response
        while (!\feof($fp)) {
            $this->rawResponse .= \fread($fp, 4096);
        }

        \fclose($fp);

        // parse raw response
        $this->parseResponse();
        return $this;
    }

    public function ssl()
    {
        $context = \stream_context_create();
        $result = \stream_context_set_option($context, 'ssl', 'verify_host', true);
        if (!empty($opts['cert'])) {
            $result = \stream_context_set_option($context, 'ssl', 'cafile', $opts['cert']);
            $result = \stream_context_set_option($context, 'ssl', 'verify_peer', true);
        } else {
            $result = \stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
        }
    }

}
