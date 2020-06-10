<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client\Swoole;

use PhpComp\Http\Client\AbstractClient;
use PhpComp\Http\Client\ClientUtil;
use Swoole\Coroutine\Http2\Client;
use Swoole\Coroutine\Http2\Request;
use function class_exists;
use function strtoupper;
use function array_merge;

/**
 * Class CoClient2 - http2 client
 * @package PhpComp\Http\Client\Swoole
 * @link https://wiki.swoole.com/wiki/page/856.html
 */
class CoClient2 extends AbstractClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return class_exists(Client::class);
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
            $options['method'] = strtoupper($method);
        }

        // get request url info
        $info = ClientUtil::parseUrl($this->buildFullUrl($url));

        // enable SSL verify
        // options: 'sslVerify' => false/true,
        $sslVerify = (bool)$this->getOption('sslVerify');

        if ($info['scheme'] === 'https' || $info['scheme'] === 'wss') {
            $sslVerify = true;
        }

        $client = new Client($info['host'], $info['port'], $sslVerify);
        // some client option
        $client->set([
            // 'timeout' => -1
            'timeout' => (int)$options['timeout'],
            'ssl_host_name' => $info['host']
        ]);
        $client->connect();

        $uri = $info['path'];
        if ($info['query']) {
            $uri .= '?' . $info['query'];
        }

        $req = new Request();
        $req->path = $uri;
        $this->prepareRequest($req, $headers, $options);

        if ($data) {
            $req->data = $data;
        }

        // send request
        $client->send($req);
        $resp = $client->recv();
        $this->responseBody = $resp->data;
        $client->close();

        return $this;
    }

    private function prepareRequest(Request $request, array $headers, array $options): void
    {
        // merge global options data.
        $options = array_merge($this->options, $options);

        // set method
        $request->method = $this->formatAndCheckMethod($options['method']);

        // set headers
        if ($headers = array_merge($this->headers, $options['headers'], $headers)) {
            $request->headers = $headers;
        }

        // set cookies
        if ($cookies = array_merge($this->cookies, $options['cookies'])) {
            $request->cookies = $cookies;
        }
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}
