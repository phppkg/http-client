<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\Client;

use InvalidArgumentException;
use PhpPkg\Http\Client\Exception\ClientException;
use Toolkit\Stdlib\Str\UrlHelper;
use function array_merge;
use function function_exists;
use function is_resource;
use function sprintf;
use function stream_socket_client;
use function strtoupper;
use const STREAM_CLIENT_CONNECT;
use const STREAM_CLIENT_PERSISTENT;

/**
 * Class StreamClient
 *
 * @package PhpPkg\Http\Client
 */
class StreamClient extends FSockClient
{
    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return function_exists('stream_socket_client');
    }

    /**
     * stream context. it's create by stream_context_create()
     *
     * @param array $opts
     *
     * @return mixed|resource
     */
    protected function buildStreamContext(array $opts): mixed
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

        $method      = ClientUtil::formatAndCheckMethod($opts['method']);
        $httpOptions = [
            'method'  => $method,
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

        // merge global options data.
        $options = array_merge($this->options, $options);

        // get request url info
        $info = UrlHelper::parse2($this->buildFullUrl($url));
        $ctx  = $this->buildStreamContext($options);

        $timeout   = (int)$options['timeout'];
        $socketUrl = sprintf('tcp://%s:%d', $info['host'], (int)$info['port']);
        $flags     = STREAM_CLIENT_CONNECT;

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

        $this->buildRequestAndWrite($handle, $info, $headers, $options, $data);

        return $this;
    }
}
