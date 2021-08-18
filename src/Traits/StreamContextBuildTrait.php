<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client\Traits;

use InvalidArgumentException;
use PhpComp\Http\Client\ClientUtil;
use PhpComp\Http\Client\StreamContext;
use function array_merge;
use function http_build_query;
use function is_resource;
use function sprintf;
use function strlen;

/**
 * Trait StreamContextBuildTrait
 *
 * @package PhpComp\Http\Client\Traits
 */
trait StreamContextBuildTrait
{
    /**
     * @var string
     */
    protected $fullUrl = '';

    /**
     * build stream context. it's create by stream_context_create()
     *
     * @param string     $fullUrl
     * @param array      $headers
     * @param array      $opts
     * @param mixed|null $data
     *
     * @return resource
     */
    protected function buildStreamContext(string $fullUrl, array $headers, array $opts, $data = null)
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

        // build cookies value
        if ($cookies = array_merge($this->cookies, $opts['cookies'])) {
            // "Cookie: name=value; name1=value1"
            $headers['Cookie'] = http_build_query($cookies, '', '; ');
        }

        $info    = ClientUtil::parseUrl($fullUrl);
        $headers = array_merge($this->headers, $opts['headers'], $headers);
        $headers = ClientUtil::ucwordArrayKeys($headers);

        if (!isset($headers['Host'])) {
            $headers['Host'] = $info['host'];
        }

        $body   = '';
        $method = ClientUtil::formatAndCheckMethod($opts['method']);

        $this->fullUrl = $fullUrl;

        if ($data) {
            // allow submit body data
            if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
                $body = ClientUtil::buildBodyByContentType($headers, $data);
                // add Content-length
                $headers['Content-Length'] = strlen($body);
            } else {
                $this->fullUrl = ClientUtil::buildURL($fullUrl, $data);
            }
        }

        $httpOptions = [
            'method'  => $method,
            'timeout' => (int)$opts['timeout'], // 超时
            'header'  => ClientUtil::formatHeaders($headers),
            'content' => $body,
        ];

        // 设置代理
        if ($proxy = $opts['proxy']) {
            $httpOptions['proxy'] = sprintf('tcp://%s:%d', $proxy['host'], (int)$proxy['port']);
        }

        StreamContext::setHTTPOptions($context, $httpOptions);

        // user can custom set context options.
        // please refer StreamContext::createXXOptions()
        if (isset($opts['streamContextOptions'])) {
            StreamContext::setOptions($context, (array)$opts['streamContextOptions']);
        }

        return $context;
    }

    /**
     * @return string
     */
    public function getFullUrl(): string
    {
        return $this->fullUrl;
    }
}
