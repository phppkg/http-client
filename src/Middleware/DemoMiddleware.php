<?php declare(strict_types=1);

namespace PhpComp\Http\Client\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class DemoMiddleware
 *
 * @package PhpComp\Http\Client\Middleware
 */
class DemoMiddleware implements MiddlewareInterface
{
    /**
     * @param RequestInterface $request
     * @param \Closure         $next
     *
     * @return ResponseInterface
     */
    public function request(RequestInterface $request, \Closure $next): ResponseInterface
    {
        return $next($request);
    }
}
