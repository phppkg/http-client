<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/22
 * Time: 2:01 PM
 */

namespace PhpComp\Http\Client\Error;

use Psr\Http\Client\ClientExceptionInterface;

/**
 * Class ClientException
 * @package PhpComp\Http\Client\Error
 */
class ClientException extends \RuntimeException implements ClientExceptionInterface
{
}
