<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client;

use function array_merge;
use function stream_context_create;
use function stream_context_get_options;
use function stream_context_get_params;
use function stream_context_set_option;

/**
 * Class StreamContext
 *
 * @package PhpComp\Http\Client
 */
class StreamContext
{
    /**
     * context params
     *
     * @link https://secure.php.net/manual/zh/function.stream-notification-callback.php
     * stream_context_set_params($ctx, array("notification" => "stream_notification_callback"));
     */

    /**
     * @param array $options
     * @param array $params
     *
     * @return resource
     */
    public static function create(array $options = [], array $params = [])
    {
        return stream_context_create($options, $params);
    }

    /**
     * @param resource $ctx it's created by stream_context_create()
     * @param array    $options
     *                      [
     *                      'http' => [], // please {@see StreamContext::createHTTPOptions}
     *                      'ssl' => [], // please {@see StreamContext::createSSLOptions}
     *                      ]
     */
    public static function setOptions($ctx, array $options): void
    {
        stream_context_set_option($ctx, $options);
    }

    /**
     * @param resource $ctx
     * @param array    $options please {@see StreamContext::createHTTPOptions}
     *                          [
     *                          'method' => 'GET',
     *                          ...
     *                          ]
     */
    public static function setHTTPOptions($ctx, array $options): void
    {
        foreach ($options as $option => $value) {
            stream_context_set_option($ctx, 'http', $option, $value);
        }
    }

    /**
     * @param resource $ctx
     * @param array    $options please {@see StreamContext::createSSLOptions}
     *                          [
     *                          'peer_name' => '..',
     *                          ...
     *                          ]
     */
    public static function setSSLOptions($ctx, array $options): void
    {
        foreach ($options as $option => $value) {
            stream_context_set_option($ctx, 'ssl', $option, $value);
        }
    }

    /**
     * HTTP context 选项 - 提供给 http:// 和 https:// 传输协议的 context 选项。 transports.
     *
     * @link https://secure.php.net/manual/zh/context.http.php
     *
     * @param array $options
     * @param bool  $addWrapper
     *
     * @return array
     */
    public static function createHTTPOptions(array $options, bool $addWrapper = true): array
    {
        $options = array_merge([
            // 远程服务器支持的 GET，POST 或其它 HTTP 方法
            'method'           => 'GET',
            // 请求期间发送的额外 header 。(string|array)
            // 在此选项的值将覆盖其他值 （诸如 User-agent:， Host: 和 Authentication:）
            'header'           => [],
            // 要发送的 header User-Agent: 的值。
            // 如果在上面的 header context 选项中没有指定 user-agent，此值将被使用。
            'user_agent'       => '',
            // 在 header 后面要发送的额外数据。通常使用POST或PUT请求
            'content'          => '',
            // URI 指定的代理服务器的地址。(e.g. tcp://proxy.example.com:5100).
            'proxy'            => '',
            // 跟随 Location header 的重定向。设置为 0 以禁用。
            // 默认值是 1
            'follow_location'  => 1,
            // 跟随重定向的最大次数。值为 1 或更少则意味不跟随重定向。
            // 默认值是 20。
            'max_redirects'    => 1,
            // HTTP 协议版本。 默认值是 1.0。
            // Note：如果此值设置为 1.1, 必须添加 header 'Connection: close'，不然会阻塞直到超时
            'protocol_version' => '1.0',
            // 读取超时时间，单位为秒（s），用 float 指定(e.g. 10.5)。
            'timeout'          => 3,
            // 即使是故障状态码依然获取内容。 默认值为 FALSE
            'ignore_errors'    => false,
        ], $options);

        if ($addWrapper) {
            return ['http' => $options];
        }

        return $options;
    }

    /**
     * SSL 上下文选项 - ssl:// 和 tls:// 传输协议上下文选项清单
     *
     * @link https://secure.php.net/manual/zh/context.ssl.php
     * @Note 因为 ssl:// 是 https:// 和 ftps:// 的底层传输协议，
     *       所以，ssl:// 的上下文选项也同样适用于 https:// 和 ftps:// 上下文。
     *
     * @param array $options
     * @param bool  $addWrapper
     *
     * @return array
     */
    public static function createSSLOptions(array $options, bool $addWrapper = true): array
    {
        $options = array_merge([
            // 要连接的服务器名称。如果未设置，那么服务器名称将根据打开 SSL 流的主机名称猜测得出。
            'peer_name'        => '',
            // 是否需要验证 SSL 证书
            'verify_peer'      => true,
            // 是否需要验证 peer name
            'verify_peer_name' => true,
            // 当设置 verify_peer 为 true 时， 用来验证远端证书所用到的 CA 证书。
            // 本选项值为 CA 证书在本地文件系统的全路径及文件名。
            'cafile'           => '',
            // 如果未设置 cafile，或者 cafile 所指的文件不存在时，会在 capath 所指定的目录搜索适用的证书
            'capath'           => '',
            // 本地证书路径。
            // 必须是 PEM 格式，并且包含本地的证书及私钥。也可以包含证书颁发者证书链。
            // 也可以通过 local_pk 指定包含私钥的独立文件
            'local_cert'       => '',
            // 如果使用独立的文件来存储证书（local_cert）和私钥， 那么使用此选项来指明私钥文件的路径
            'local_pk'         => '',
            // local_cert 文件的密码
            'passphrase'       => '',
            // 如果证书链条层次太深，超过了本选项的设定值，则终止验证。
            // 默认情况下不限制证书链条层次深度。
            'verify_depth'     => 0,
            // 如果设置，则禁用 TLS 压缩，有助于减轻恶意攻击
            // 'disable_compression' => boolean,

            // 当远程服务器证书的摘要和指定的散列值不相同的时候， 终止操作。
            // string 会根据字符串的长度来检测所使用的散列算法：“md5”（32 字节）还是“sha1”（40 字节)
            // array 数组的键表示散列算法名称，其对应的值是预期的摘要值。
            'peer_fingerprint' => '',
        ], $options);

        if ($addWrapper) {
            return ['ssl' => $options];
        }

        return $options;
    }

    /**
     * @param resource $ctxOrStream
     *
     * @return array
     */
    public static function getOptions($ctxOrStream): array
    {
        return stream_context_get_options($ctxOrStream);
    }

    /**
     * @param resource $ctxOrStream
     *
     * @return array
     */
    public static function getParams($ctxOrStream): array
    {
        return stream_context_get_params($ctxOrStream);
    }
}
