<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/11/21
 * Time: 2:47 PM
 */

namespace PhpComp\Http\Client;

/**
 * Class ClientUtil
 * @package PhpComp\Http\Client
 */
class ClientUtil
{
    /**
     * @param array $src
     * @param array $append
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
     * @param string $url
     * @return bool
     */
    public static function isFullURL(string $url): bool
    {
        return 0 === \strpos($url, 'http:') || 0 === \strpos($url, 'https:') || 0 === strpos($url, '//');
    }

    /**
     * @param string $url
     * @param array|object $data
     * @return string
     */
    public static function buildURL(string $url, $data = null)
    {
        if ($data && ($param = \http_build_query($data))) {
            $url .= (strpos($url, '?') ? '&' : '?') . $param;
        }

        return $url;
    }

    // Build arrays of values we need to decode before parsing
    protected static $entities = array(
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
    );

    protected static $replacements = array(
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
    );

    /**
     * [urlEncode 会先转换编码]
     * $url="ftp://ud03:password@www.xxx.net/中文/中文.rar";
     * $url1 =  url_encode($url);
     * //ftp://ud03:password@www.xxx.net/%C3%A4%C2%B8%C2%AD%C3%A6%C2%96%C2%87/%C3%A4%C2%B8%C2%AD%C3%A6%C2%96%C2%87.rar
     * $url2 =  urldecode($url);
     * echo $url1.PHP_EOL.$url2;
     * @param  string $url [description]
     * @return mixed|string [type]      [description]
     */
    public static function encodeURL(string $url)
    {
        if (!$url = \trim($url)) {
            return '';
        }

        // 若已被编码的url，将被解码，再继续重新编码
        $url = \urldecode($url);
        $encodeUrl = \rawurlencode(\mb_convert_encoding($url, 'utf-8'));

        // $url  = rawurlencode($url);

        return \str_replace(self::$entities, self::$replacements, $encodeUrl);
    }
}
