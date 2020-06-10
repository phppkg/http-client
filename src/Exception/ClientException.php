<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * Class ClientException
 *
 * @package PhpComp\Http\Client\Exception
 */
class ClientException extends RuntimeException implements ClientExceptionInterface
{
}
