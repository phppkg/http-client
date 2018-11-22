<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-27
 * Time: 18:44
 */

namespace PhpComp\Http\Client\Curl;

use PhpComp\Http\Client\AbstractClient;
use PhpComp\Http\Client\ClientUtil;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class CurlLite - a lite curl tool
 * @package PhpComp\Http\Client\Curl
 */
class CurlLite extends AbstractClient
{

    /**
     * @var int
     */
    private $errNo;

    /**
     * @var string
     */
    private $error;

    /**
     * @var array
     */
    private $_responseInfo = [];

    /**
     * @var string body string, it's from curl_exec()
     */
    protected $_responseBody = '';

///////////////////////////////////////////////////////////////////////
// main
///////////////////////////////////////////////////////////////////////

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // TODO: Implement sendRequest() method.
    }

    /**
     * Executes a CURL request with optional retries and exception on failure
     *
     * @param string $url url path
     * @param mixed $data send data
     * @param string $method
     * @param array $headers
     * @param array $options
     * @return string
     */
    public function request(string $url, $data = null, string $method = 'GET', array $headers = [], array $options = [])
    {
        $options = ClientUtil::mergeArray($this->defaultOptions, $options);

        if ($method) {
            $options['method'] = $method;
        }

        $ch = $this->createResource($url, $data, $headers, $options);

        $ret = '';
        $retries = (int)$options['retry'];
        $retries = $retries > 30 || $retries < 0 ? 3 : $retries;

        while ($retries >= 0) {
            if (($ret = \curl_exec($ch)) === false) {
                $curlErrNo = \curl_errno($ch);

                if (false === \in_array($curlErrNo, self::$canRetryErrorCodes, true)) {
                    $curlError = \curl_error($ch);

                    $this->errNo = $curlErrNo;
                    $this->error = \sprintf('Curl error (code %s): %s', $this->errNo, $curlError);
                }

                $retries--;
                continue;
            }

            break;
        }

        $this->_responseInfo = \curl_getinfo($ch);
        \curl_close($ch);

        return $ret;
    }

    /**
     * reset
     */
    public function reset()
    {
        $this->error = null;
        $this->_responseInfo = [];
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->getResponseBody();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getResponseBody();
    }

    /**
     * @return string
     */
    public function getResponseBody()
    {
        return $this->_responseBody;
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return $this->_responseInfo;
    }
}
