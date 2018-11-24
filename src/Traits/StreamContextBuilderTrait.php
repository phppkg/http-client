<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-24
 * Time: 20:03
 */

namespace PhpComp\Http\Client\Traits;

use PhpComp\Http\Client\ClientUtil;
use PhpComp\Http\Client\StreamContext;

/**
 * Trait StreamContextBuilderTrait
 * @package PhpComp\Http\Client\Traits
 */
trait StreamContextBuilderTrait
{
    /**
     * @var string
     */
    protected $fullUrl = '';

    /**
     * build stream context. it's create by stream_context_create()
     * @param string $fullUrl
     * @param array $headers
     * @param array $opts
     * @param mixed|null $data
     * @return resource
     */
    protected function buildStreamContext(string $fullUrl, array $headers, array $opts, $data = null)
    {
        if (isset($opts['streamContext'])) {
            $context = $opts['streamContext'];

            // Suppress the error since we'll catch it below
            if (\is_resource($context) && get_resource_type($context) !== 'stream-context') {
                throw new \InvalidArgumentException("Stream context in options[streamContext] isn't a valid context resource");
            }
        } else {
            $context = StreamContext::create();
        }

        // build cookies value
        if ($cookies = \array_merge($this->cookies, $opts['cookies'])) {
            // "Cookie: name=value; name1=value1"
            $headers['Cookie'] = \http_build_query($cookies, '', '; ');
        }

        $info = ClientUtil::parseUrl($fullUrl);
        $headers = \array_merge($this->headers, $opts['headers'], $headers);
        $headers = ClientUtil::ucwordArrayKeys($headers);

        if (!isset($headers['Host'])) {
            $headers['Host'] = $info['host'];
        }

        $body = '';
        $method = $this->formatAndCheckMethod($opts['method']);
        $this->fullUrl = $fullUrl;

        if ($data) {
            // allow submit body data
            if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
                $body = ClientUtil::buildBodyByContentType($headers, $data);
                // add Content-length
                $headers['Content-Length'] = \strlen($body);
            } else {
                $this->fullUrl = ClientUtil::buildURL($fullUrl, $data);
            }
        }

        StreamContext::setHTTPOptions($context, [
            'method' => $method,
            'timeout' => (int)$opts['timeout'],
            'content' => $body,
        ]);

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
