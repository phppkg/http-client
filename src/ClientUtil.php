<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client;

use InvalidArgumentException;
use PhpComp\Http\Client\Exception\ClientException;
use Toolkit\Stdlib\Arr\ArrayHelper;
use Toolkit\Stdlib\Helper\JsonHelper;
use Toolkit\Stdlib\Str\UrlHelper;
use function array_merge;
use function http_build_query;
use function is_scalar;
use function mb_convert_encoding;
use function parse_url;
use function rawurlencode;
use function str_replace;
use function stripos;
use function strpos;
use function strtoupper;
use function trim;
use function ucwords;
use function urldecode;

/**
 * Class ClientUtil
 *
 * @package PhpComp\Http\Client
 */
class ClientUtil
{
    /**
     * @param array $src
     * @param array $append
     *
     * @return array
     * @deprecated please use ArrayHelper::quickMerge($append, $src)
     */
    public static function mergeArray(array $src, array $append): array
    {
        return ArrayHelper::quickMerge($append, $src);
    }

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
     *
     * @return array
     * @deprecated please use UrlHelper::parse2($url);
     */
    public static function parseUrl(string $url): array
    {
        $info = parse_url($url);
        if ($info === false) {
            throw new ClientException('invalid request url: ' . $url);
        }

        return array_merge([
            'scheme' => 'http',
            'host'   => '',
            'port'   => 80,
            'path'   => '/',
            'query'  => '',
        ], $info);
    }

    /**
     * @param string $url
     * @param null   $data
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
     * @param string|array|object $data body data
     *
     * @return string
     */
    public static function buildBodyByContentType(array &$headers, $data): string
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
