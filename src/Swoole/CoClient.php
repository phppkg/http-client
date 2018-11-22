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
        // get request url
        $url = $this->buildUrl($url);
        $info = \parse_url($url);
        if ($info === false) {
            throw new ClientException('invalid request url');
        }

        $port = empty($info['port']) ? 80 : $info['port'];
        $this->client = $client = new Client($info['host'], $port);
        $client->setMethod(\strtoupper($method));

        if ($data) {
            $client->setData($data);
        }

        $client->execute($info['path']);

        // check error
        if ($this->errNo = $client->errCode) {
            $this->error = \socket_strerror($client->errCode);
        } else {
            $this->responseBody = $client->body;
            $this->responseHeaders = $client->headers;
            $this->statusCode = $client->statusCode;
        }

        $client->close();
        return $this;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}
