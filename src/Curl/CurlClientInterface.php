<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client\Curl;

/**
 * Class CurlExtraInterface
 *
 * @package PhpComp\Http\Client\Curl
 */
interface CurlClientInterface
{
    /**
     * Set curl options
     *
     * @param array $options
     */
    public function setCurlOptions(array $options);

    /**
     * @return array
     */
    public function getCurlOptions(): array;
}
