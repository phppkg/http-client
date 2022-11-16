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
use Toolkit\Stdlib\Str\UrlHelper;
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
     * get from \stream_get_meta_data()
     *
     * @see https://secure.php.net/manual/zh/function.stream-get-meta-data.php
     * @var array = [
     *  'timed_out' => false,
     *  'blocked' => true,
     *  'eof' => true,
     *  'wrapper_type' => "http",
     *  'stream_type' => "tcp_socket/ssl",
     *  'mode' => "rb",
     *  'unread_bytes' => 0,
     *  'seekable' => false,
     * ]
     */
    private array $responseInfo = [];

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
     * @param array|string|null $data
     * @param string $method
     * @param array  $headers
     * @param array  $options
     *
     * @return self
     */
    public function request(
        string $url,
        array|string $data = null,
        string $method = self::GET,
        array $headers = [],
        array $options = []
    ): static {
        if ($method) {
            $options['method'] = strtoupper($method);
        }

        // get request url info
        $info = UrlHelper::parse2($this->buildFullUrl($url));

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
    public function resetResponse(): static
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
