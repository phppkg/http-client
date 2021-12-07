<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\Client\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * Class ClientException
 *
 * @package PhpPkg\Http\Client\Exception
 */
class ClientException extends RuntimeException implements ClientExceptionInterface
{
}
