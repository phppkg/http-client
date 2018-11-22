<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-30
 * Time: 17:26
 */

namespace PhpComp\Http\Client\Swoole;

use PhpComp\Http\Client\AbstractClient;
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
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return \class_exists(Client::class);
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
        // get request url info
        $info = $this->parseUrl($this->buildUrl($url));

        // create co client
        $client = $this->makeSwooleClient($info);

        $method = \strtoupper($method);
        $client->setMethod($method);

        // prepare client
        $this->prepareClient($client, [], []);

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

        // check error
        if ($this->errNo = $client->errCode) {
            $this->error = \socket_strerror($client->errCode);
        } else {
            $this->responseBody = $client->body;
            $this->responseHeaders = $client->headers;
            $this->statusCode = $client->statusCode;
        }

        $client->close();
        $this->client = $client;

        return $this;
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
        $info = $this->parseUrl($this->buildUrl($url));

        $uri = $info['path'];
        if ($info['query']) {
            $uri .= '?' . $info['query'];
        }

        $client = $this->makeSwooleClient($info);
        $this->prepareClient($client, [], []);

        return $client->download($uri, $saveAs);
    }

    private function makeSwooleClient(array $info): Client
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

        // set headers
        if ($headers = \array_merge($this->headers, $options['headers'], $headers)) {
            $client->setHeaders($headers);
        }

        // set cookies
        if ($cookies = \array_merge($this->cookies, $options['cookies'])) {
            $client->setCookies($cookies);
        }
    }

    protected function parseUrl(string $url): array
    {
        $info = \parse_url($url);
        if ($info === false) {
            throw new ClientException('invalid request url');
        }

        $info = \array_merge([
            'scheme' => 'http',
            'host' => '',
            'port' => 80,
            'path' => '/',
            'query' => '',
        ], $info);

        return $info;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}
