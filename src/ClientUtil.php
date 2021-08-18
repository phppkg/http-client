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
use Toolkit\Stdlib\Helper\JsonHelper;
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
     */
    public static function mergeArray(array $src, array $append): array
    {
        if (!$src) {
            return $append;
        }

        if (!$append) {
            return $src;
        }

        foreach ($append as $key => $val) {
            $a[$key] = $val;
        }

        return $src;
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
        return 0 === strpos($url, 'http:') || 0 === strpos($url, 'https:') || 0 === strpos($url, '//');
    }

    /**
     * @param string $url
     *
     * @return array
     */
    public static function parseUrl(string $url): array
    {
        $info = parse_url($url);
        if ($info === false) {
            throw new ClientException('invalid request url: ' . $url);
        }

        $info = array_merge([
            'scheme' => 'http',
            'host'   => '',
            'port'   => 80,
            'path'   => '/',
            'query'  => '',
        ], $info);

        return $info;
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

    // Build arrays of values we need to decode before parsing
    protected static $entities = [
        '%21',
        '%2A',
        '%27',
        '%28',
        '%29',
        '%3B',
        '%3A',
        '%40',
        '%26',
        '%3D',
        '%24',
        '%2C',
        '%2F',
        '%3F',
        '%23',
        '%5B',
        '%5D'
    ];

    protected static $replacements = [
        '!',
        '*',
        "'",
        '(',
        ')',
        ';',
        ':',
        '@',
        '&',
        '=',
        '$',
        ',',
        '/',
        '?',
        '#',
        '[',
        ']'
    ];

    /**
     * [urlEncode 会先转换编码]
     * $url="ftp://ud03:password@www.xxx.net/中文/中文.rar";
     * $url1 =  url_encode($url);
     * //ftp://ud03:password@www.xxx.net/%C3%A4%C2%B8%C2%AD%C3%A6%C2%96%C2%87/%C3%A4%C2%B8%C2%AD%C3%A6%C2%96%C2%87.rar
     * $url2 =  urldecode($url);
     * echo $url1.PHP_EOL.$url2;
     *
     * @param string $url [description]
     *
     * @return mixed|string [type]      [description]
     */
    public static function encodeURL(string $url)
    {
        if (!$url = trim($url)) {
            return '';
        }

        // 若已被编码的url，将被解码，再继续重新编码
        $url = urldecode($url);

        $encodeUrl = rawurlencode(mb_convert_encoding($url, 'utf-8'));
        // $url  = rawurlencode($url);
        return str_replace(self::$entities, self::$replacements, $encodeUrl);
    }

    /**
     * @param array               $headers
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
            $name = ucwords($name);
            // append
            $formatted[] = "$name: $value";
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
