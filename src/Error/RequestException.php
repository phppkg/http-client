<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/22
 * Time: 1:59 PM
 */

namespace PhpComp\Http\Client\Error;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Class RequestException
 * @package PhpComp\Http\Client\Error
 */
class RequestException extends \RuntimeException implements RequestExceptionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * NetworkException constructor.
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @param RequestInterface|null $request
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null, RequestInterface $request = null)
    {
        $this->request = $request;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the request.
     *
     * The request object MAY be a different object from the one passed to ClientInterface::sendRequest()
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
