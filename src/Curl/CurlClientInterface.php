<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\Client\Curl;

/**
 * Class CurlExtraInterface
 *
 * @package PhpPkg\Http\Client\Curl
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
