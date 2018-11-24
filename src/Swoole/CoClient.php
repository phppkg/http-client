<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-30
 * Time: 17:26
 */

namespace PhpComp\Http\Client\Swoole;

use PhpComp\Http\Client\AbstractClient;
use PhpComp\Http\Client\ClientUtil;
use PhpComp\Http\Client\Error\ClientException;
use Swoole\Coroutine\Http\Client;

/**
 * Class CoClient
 * @package PhpComp\Http\Client\Swoole
 * @link https://wiki.swoole.com/wiki/page/p-coroutine_http_client.html
 */
class CoClient extends AbstractClient
{
    /**
     * @var \Swoole\Coroutine\Http\Client
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
        return \class_exists(Client::class);
    }

    /**
     * File download and save
     * @param string $url
     * @param string $saveAs
     * @return bool
     * @throws \Exception
     */
    public function download(string $url, string $saveAs): bool
    {
        // get request url info
        $info = ClientUtil::parseUrl($this->buildUrl($url));

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
     * @param $url
     * @param array $data
     * @param string $method
     * @param array $headers
     * @param array $options
     * @return $this
     */
    public function request(string $url, $data = null, string $method = self::GET, array $headers = [], array $options = [])
    {
        if ($method) {
            $options['method'] = \strtoupper($method);
        }

        // get request url info
        $info = ClientUtil::parseUrl($this->buildUrl($url));

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
     * @return $this
     */
    public function receive()
    {
        if ($this->defer && $this->client !== null) {
            // receive response
            $this->client->recv($this->getTimeout());
            $this->collectResponse($this->client);
        }

        return $this;
    }

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

    private function prepareClient(Client $client, array $headers, array $options)
    {
        // some client option
        $client->set([
            // 'timeout' => -1
            'timeout' => $this->getTimeout(),
        ]);

        // merge global options data.
        $options = \array_merge($this->options, $options);

        // set method
        $method = $this->formatAndCheckMethod($options['method']);
        $client->setMethod($method);

        // set headers
        if ($headers = \array_merge($this->headers, $options['headers'], $headers)) {
            $client->setHeaders($headers);
        }

        // set cookies
        if ($cookies = \array_merge($this->cookies, $options['cookies'])) {
            $client->setCookies($cookies);
        }

        // open defer
        if ($this->defer) {
            $client->setDefer(true);
        }
    }

    private function collectResponse(Client $client)
    {
        // check error
        if ($errno = $client->errCode) {
            throw new ClientException(\socket_strerror($client->errCode), $errno);
        }

        $this->statusCode = $client->statusCode;
        $this->responseBody = $client->body;
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
     * @return CoClient
     */
    public function setDefer(bool $defer = true)
    {
        $this->defer = $defer;
        return $this;
    }
}
