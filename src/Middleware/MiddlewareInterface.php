<?php declare(strict_types=1);

namespace PhpPkg\Http\Client\Middleware;

use Closure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface MiddlewareInterface
 *
 * @package PhpPkg\Http\Client\Middleware
 */
interface MiddlewareInterface
{
    /**
     * @param RequestInterface $request
     * @param Closure          $next
     *
     * @return ResponseInterface
     */
    public function request(RequestInterface $request, Closure $next): ResponseInterface;
}
