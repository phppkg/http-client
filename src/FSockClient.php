<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-30
 * Time: 17:26
 */

namespace PhpComp\Http\Client;

/**
 * Class FSocketOpen client
 * @package PhpComp\Http\Client
 */
class FSockClient extends AbstractClient
{
    use RawResponseParserTrait;

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

    protected function buildHttpData(array $info, array $headers, array $opts, $data)
    {
        $uri = $info['path'];
        if ($info['query']) {
            $uri .= '?' . $info['query'];
        }

        // merge global options data.
        $opts = \array_merge($this->options, $opts);

        // build cookies value
        if ($cookies = \array_merge($this->cookies, $opts['cookies'])) {
            // "Cookie: name=value; name1=value1"
            $headers['Cookie'] = \http_build_query($cookies, '', '; ');
        }

        $headers = \array_merge($this->headers, $opts['headers'], $headers);
        $headers = ClientUtil::ucwordArrayKeys($headers);

        if (!isset($headers['Host'])) {
            $headers['Host'] = $info['host'];
        }

        $method = $this->formatAndCheckMethod($opts['method']);

        // $heads[] = "Host: www.example.com\r\n";
        // $heads[] = "Connection: Close\r\n";

        $body = '';
        if ($data) {
            // allow submit body
            if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
                $body = $this->buildBodyByContentType($headers, $data);
            } else {
                $uri = ClientUtil::buildURL($uri, $data);
            }
        }

        // close connection. if not add, will blocked.
        if (!isset($headers['Connection'])) {
            $headers['Connection'] = 'close';
        }

        $fmtHeaders = $this->formatHeaders($headers);

        // eg. "GET / HTTP/1.1\r\nHost: www.example.com\r\n\r\n"
        return \sprintf(
            "%s %s HTTP/1.1\r\n%s\r\n\r\n%s",
            $method, $uri, \implode("\r\n", $fmtHeaders), $body
        );
    }

    private function buildBodyByContentType(array &$headers, $data): string
    {
        $defContentType = 'application/x-www-form-urlencoded';

        if (\is_scalar($data)) {
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = $defContentType;
            }

            return (string)$data;
        }

        // data is array or object.
        if (isset($headers['Content-Type'])) {
            $ct = $headers['Content-Type'];

            // application/x-www-form-urlencoded
            if (\stripos($ct, 'x-www-form-urlencoded')) {
                return \http_build_query($data);
            }

            if (\stripos($ct, 'json')) {
                return (string)\json_encode($data);
            }
        } else {
            $headers['Content-Type'] = $defContentType;
        }

        return \http_build_query($data);
    }
}
