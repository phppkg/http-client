<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\Client;

use InvalidArgumentException;
use Toolkit\Stdlib\Helper\JsonHelper;
use Toolkit\Stdlib\Str\UrlHelper;
use function http_build_query;
use function is_scalar;
use function stripos;
use function strpos;
use function strtoupper;
use function ucwords;

/**
 * Class ClientUtil
 *
 * @package PhpPkg\Http\Client
 */
class ClientUtil
{
    /**
     * @param array $arr
     *
     * @return array
     */
    public static function ucwordArrayKeys(array $arr): array
    {
        $newMap = [];
        foreach ($arr as $key => $value) {
            $newMap[ucwords($key)] = $value;
        }

        return $newMap;
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public static function isFullURL(string $url): bool
    {
        return UrlHelper::isFullUrl($url);
    }

    /**
     * @param string $url
     * @param null $data
     *
     * @return string
     */
    public static function buildURL(string $url, $data = null): string
    {
        if ($data && ($query = http_build_query($data))) {
            $url .= (strpos($url, '?') ? '&' : '?') . $query;
        }

        return $url;
    }

    /**
     * @param string $url
     *
     * @return string
     * @deprecated please use UrlHelper::encode2($url);
     */
    public static function encodeURL(string $url): string
    {
        return UrlHelper::encode2($url);
    }

    /**
     * @param array $headers
     * @param object|array|string $data body data
     *
     * @return string
     */
    public static function buildBodyByContentType(array &$headers, object|array|string $data): string
    {
        $defContentType = 'application/x-www-form-urlencoded';

        if (is_scalar($data)) { // string.
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = $defContentType;
            }

            return (string)$data;
        }

        // data is array or object.
        if (isset($headers['Content-Type'])) {
            $ct = $headers['Content-Type'];

            // application/x-www-form-urlencoded
            if (stripos($ct, 'x-www-form-urlencoded')) {
                return http_build_query($data);
            }

            // application/json
            if (stripos($ct, 'json')) {
                return JsonHelper::encode($data);
            }
        } else {
            $headers['Content-Type'] = $defContentType;
        }

        return http_build_query($data);
    }

    /**
     * convert [key => val] to ["key: val"]
     *
     * @param array $headers
     *
     * @return array
     */
    public static function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $name => $value) {
            $formatted[] = ucwords($name) . ': ' . $value;
        }

        return $formatted;
    }

    /**
     * @param string $method
     *
     * @return string
     */
    public static function formatAndCheckMethod(string $method): string
    {
        $method = strtoupper($method);
        if (!isset(ClientInterface::SUPPORTED_METHODS[$method])) {
            throw new InvalidArgumentException("The method [$method] is not supported!");
        }

        return $method;
    }

}
