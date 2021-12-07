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
use PhpPkg\Http\Client\Exception\RequestException;
use PhpPkg\Http\Client\Traits\BuildRawHttpRequestTrait;
use PhpPkg\Http\Client\Traits\ParseRawResponseTrait;
use function array_merge;
use function fclose;
use function feof;
use function fread;
use function fsockopen;
use function function_exists;
use function fwrite;
use function pfsockopen;
use function stream_get_meta_data;
use function stream_set_timeout;
use function strtoupper;

/**
 * Class FSockClient - powered by func fsockopen()
 *
 * @package PhpPkg\Http\Client
 */
class FSockClient extends AbstractClient
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
        return function_exists('fsockopen');
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
        $info = ClientUtil::parseUrl($this->buildFullUrl($url));

        // merge global options data.
        $options = array_merge($this->options, $options);
        $timeout = (int)$options['timeout'];

        // open socket connection
        if (isset($options['persistent']) && $options['persistent']) {
            $handle = pfsockopen($info['host'], $info['port'], $errno, $error, $timeout);
        } else {
            $handle = fsockopen($info['host'], $info['port'], $errno, $error, $timeout);
        }

        // if open fail
        if (!$handle) {
            throw new ClientException($error, $errno);
        }

        $string = $this->buildRawHttpData($info, $headers, $options, $data);

        // set timeout
        stream_set_timeout($handle, $timeout);

        // send request
        if (false === fwrite($handle, $string)) {
            throw new RequestException('send request to server is fail');
        }

        // read response
        while (!feof($handle)) {
            $this->rawResponse .= fread($handle, 4096);
        }

        // save some info
        $this->responseInfo = stream_get_meta_data($handle);

        fclose($handle);

        // parse raw response
        $this->parseResponse();

        return $this;
    }

    /**
     * @return $this
     */
    public function resetResponse(): ClientInterface
    {
        $this->rawResponse    = '';
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
