<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client\Swoole;

use Exception;
use PhpComp\Http\Client\AbstractClient;
use PhpComp\Http\Client\ClientInterface;
use PhpComp\Http\Client\ClientUtil;
use PhpComp\Http\Client\Exception\ClientException;
use Swoole\Coroutine\Http\Client;
use function array_merge;
use function class_exists;
use function socket_strerror;
use function strtoupper;

/**
 * Class CoClient
 *
 * @package PhpComp\Http\Client\Swoole
 * @link    https://wiki.swoole.com/wiki/page/p-coroutine_http_client.html
 */
class CoClient extends AbstractClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var bool
     */
    private $defer = false;

    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return class_exists(Client::class);
    }

    /**
     * File download and save
     *
     * @param string $url
     * @param string $saveAs
     *
     * @return bool
     * @throws Exception
     */
    public function download(string $url, string $saveAs): bool
    {
        // get request url info
        $info = ClientUtil::parseUrl($this->buildFullUrl($url));

        $uri = $info['path'];
        if ($info['query']) {
            $uri .= '?' . $info['query'];
        }

        $client = $this->newSwooleClient($info);
        $this->prepareClient($client, [], []);

        return $client->download($uri, $saveAs);
    }

    /**
     * Send request to remote URL
     *
     * @param        $url
     * @param array  $data
     * @param string $method
     * @param array  $headers
     * @param array  $options
     *
     * @return $this
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

        // create co client
        $client = $this->newSwooleClient($info);

        // prepare client
        $this->prepareClient($client, $headers, $options);

        // add data
        if ($data) {
            $client->setData($data);
        }

        $uri = $info['path'];
        if ($info['query']) {
            $uri .= '?' . $info['query'];
        }

        // do send request.
        $client->execute($uri);

        // not use defer.
        if (!$this->defer) {
            $this->collectResponse($client);
            $client->close();
        }

        $this->client = $client;
        return $this;
    }

    /**
     * only available on defer is true
     *
     * @return $this
     */
    public function receive(): self
    {
        if ($this->defer && $this->client !== null) {
            // receive response
            $this->client->recv($this->getTimeout());
            $this->collectResponse($this->client);
        }

        return $this;
    }

    /**
     * @param array $info
     *
     * @return Client
     */
    private function newSwooleClient(array $info): Client
    {
        // enable SSL verify
        // options: 'sslVerify' => false/true,
        $sslVerify = (bool)$this->getOption('sslVerify');

        if ($info['scheme'] === 'https' || $info['scheme'] === 'wss') {
            $sslVerify = true;
        }

        // create co client
        return new Client($info['host'], $info['port'], $sslVerify);
    }

    /**
     * @param Client $client
     * @param array  $headers
     * @param array  $options
     */
    private function prepareClient(Client $client, array $headers, array $options): void
    {
        // merge global options data.
        $options   = array_merge($this->options, $options);
        $coOptions = [
            // 'timeout' => -1
            'timeout' => (int)$options['timeout'],
        ];

        // 代理配置
        if ($proxy = $options['proxy']) {
            $coOptions['http_proxy_host'] = $proxy['host'];
            $coOptions['http_proxy_port'] = $proxy['port'];
        }

        /**
         * @see https://wiki.swoole.com/wiki/page/p-client_setting.html
         * @see https://wiki.swoole.com/wiki/page/726.html
         * 'timeout'
         * 'ssl_cert_file'
         * 'ssl_key_file'
         * ... more
         */
        if (isset($options['coOptions'])) {
            $coOptions = array_merge($coOptions, $options['coOptions']);
        }

        // some swoole client option
        $client->set($coOptions);

        // set method
        $method = $this->formatAndCheckMethod($options['method']);
        $client->setMethod($method);

        // set headers
        if ($headers = array_merge($this->headers, $options['headers'], $headers)) {
            $client->setHeaders($headers);
        }

        // set cookies
        if ($cookies = array_merge($this->cookies, $options['cookies'])) {
            $client->setCookies($cookies);
        }

        // open defer
        if ($this->defer) {
            $client->setDefer(true);
        }
    }

    /**
     * @param Client $client
     */
    private function collectResponse(Client $client): void
    {
        // check error
        if ($errno = $client->errCode) {
            throw new ClientException(socket_strerror($client->errCode), $errno);
        }

        $this->statusCode      = $client->statusCode;
        $this->responseBody    = $client->body;
        $this->responseHeaders = $client->headers;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return bool
     */
    public function isDefer(): bool
    {
        return $this->defer;
    }

    /**
     * @param bool $defer
     *
     * @return CoClient
     */
    public function setDefer(bool $defer = true): ClientInterface
    {
        $this->defer = $defer;
        return $this;
    }
}
