<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\Client\Exception;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Throwable;

/**
 * Class RequestException
 *
 * @package PhpPkg\Http\Client\Exception
 */
class NetworkException extends RuntimeException implements NetworkExceptionInterface
{
    /**
     * @var RequestInterface
     */
    private ?RequestInterface $request;

    /**
     * NetworkException constructor.
     *
     * @param string                $message
     * @param int                   $code
     * @param Throwable|null        $previous
     * @param RequestInterface|null $request
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?RequestInterface $request = null
    ) {
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
