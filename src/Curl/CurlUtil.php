<?php declare(strict_types=1);

namespace PhpPkg\Http\Client\Curl;

use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPGET;
use const CURLOPT_NOBODY;
use const CURLOPT_POST;
use const CURLOPT_PUT;

/**
 * class CurlUtil
 */
class CurlUtil
{
    /**
     * set request method
     *
     * @param array $curlOptions
     * @param string $method
     */
    public static function setMethodToOption(array &$curlOptions, string $method): void
    {
        switch ($method) {
            case 'GET':
                $curlOptions[CURLOPT_HTTPGET] = true;
                break;
            case 'POST':
                $curlOptions[CURLOPT_POST] = true;
                break;
            case 'PUT':
                $curlOptions[CURLOPT_PUT] = true;
                break;
            case 'HEAD':
                $curlOptions[CURLOPT_HEADER] = true;
                $curlOptions[CURLOPT_NOBODY] = true;
                break;
            default:
                $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
        }
    }
}
