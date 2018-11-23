<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-30
 * Time: 17:26
 */

namespace PhpComp\Http\Client;

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
        $timeout = $this->getTimeout();

        // open sock
        $fp = \fsockopen($info['host'], $info['port'], $errno, $error, $timeout);

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

}
