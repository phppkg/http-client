<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

namespace PhpPkg\Http\Client\Curl;

use CurlHandle;
use CurlMultiHandle;
use PhpPkg\Http\Client\ClientUtil;
use RuntimeException;
use Toolkit\Stdlib\Arr\ArrayHelper;
use Toolkit\Stdlib\Helper\Assert;
use Toolkit\Stdlib\Str\UrlHelper;
use function array_merge;
use function curl_errno;
use function curl_error;
use function curl_init;
use function curl_multi_add_handle;
use function curl_multi_close;
use function curl_multi_exec;
use function curl_multi_getcontent;
use function curl_multi_init;
use function curl_multi_remove_handle;
use function curl_multi_select;
use function curl_setopt;
use function curl_setopt_array;
use function strtoupper;
use function trim;
use function usleep;
use const CURLM_CALL_MULTI_PERFORM;
use const CURLM_OK;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_ENCODING;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_IPRESOLVE;
use const CURLOPT_MAXREDIRS;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_PROXY;
use const CURLOPT_PROXYPORT;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;

/**
 * Class CurlMulti
 *
 * @package PhpPkg\Http\Client\Curl
 */
class CurlMulti // extends CurlLite
{
    /**
     * @var array
     */
    private array $errors = [];

    /**
     * @var CurlHandle[]
     */
    private array $chMap = [];

    /**
     * @var CurlMultiHandle|null
     */
    private ?CurlMultiHandle $mh = null;

    /**
     * base Url
     *
     * @var string
     */
    protected string $baseUrl = '';

    /**
     * @var array
     */
    protected array $defaultOptions = [
        'uri'       => '',
        'method'    => 'GET', // 'POST'
        'retry'     => 3,
        'timeout'   => 5,

        // enable SSL verify
        'sslVerify' => false,

        'headers'     => [// name => value
        ],
        'proxy'       => [
            // 'host' => '',
            // 'port' => '',
        ],
        'data'        => [],
        'curlOptions' => [],
    ];

    /**
     * @var array
     */
    private array $options;

    /**
     * @param array $options
     *
     * @return CurlMulti
     */
    public static function create(array $options = []): CurlMulti
    {
        return new static($options);
    }

    /**
     * CurlMulti constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * make Multi
     *
     * @param array $data
     *
     * @return self
     */
    public function build(array $data): self
    {
        $this->mh = curl_multi_init();

        foreach ($data as $key => $opts) {
            $opts = ArrayHelper::quickMerge($this->options, $opts);

            $this->chMap[$key] = $this->createResource($opts['url'], [], [], $opts);

            curl_multi_add_handle($this->mh, $this->chMap[$key]);
        }

        // unset($data);
        return $this;
    }

    /**
     * @param string $url
     * @param mixed|null $data
     * @param array $headers
     * @param array $options
     *
     * @return $this
     */
    public function append(string $url, mixed $data = null, array $headers = [], array $options = []): self
    {
        $options = array_merge($this->options, $options);
        // append
        $this->chMap[] = $ch = $this->createResource($url, $data, $headers, $options);

        curl_multi_add_handle($this->mh, $ch);

        return $this;
    }

    /**
     * execute multi request
     *
     * @link https://secure.php.net/manual/zh/function.curl-multi-select.php
     *
     * @param null|CurlMultiHandle $mh
     *
     * @return bool|array
     */
    public function execute(?CurlMultiHandle $mh = null): bool|array
    {
        if (!($mh = $mh ?: $this->mh)) {
            return false;
        }

        $active = true;
        $mrc    = CURLM_OK;

        while ($active && $mrc === CURLM_OK) {
            // Solve CPU 100% usage
            if (curl_multi_select($mh) === -1) {
                usleep(100);
            }

            do {
                $mrc = curl_multi_exec($mh, $active);
                // curl_multi_select($mh); // Solve CPU 100% usage
            } while ($mrc === CURLM_CALL_MULTI_PERFORM);
        }

        $responses = [];

        // 关闭全部句柄
        foreach ($this->chMap as $key => $ch) {
            curl_multi_remove_handle($mh, $ch);

            if ($eno = curl_errno($ch)) {
                $eor                = curl_error($ch);
                $this->errors[$key] = [$eno, $eor];
                $responses[$key]    = null;
            } else {
                $responses[$key] = curl_multi_getcontent($ch);
            }
        }

        curl_multi_close($mh);
        return $responses;
    }

    /**
     * @param string $url
     * @param mixed|null $data
     * @param array $headers
     * @param array $opts
     *
     * @return CurlHandle
     */
    public function createResource(string $url, mixed $data = null, array $headers = [], array $opts = []): CurlHandle
    {
        $ch = curl_init();
        Assert::notEmpty($ch, 'init an curl handle failed');

        $curlOptions = [
            // 设置超时
            CURLOPT_TIMEOUT        => (int)$opts['timeout'],
            CURLOPT_CONNECTTIMEOUT => (int)$opts['timeout'],

            // 要求返回结果而不是输出到屏幕上
            CURLOPT_RETURNTRANSFER => true,

            // 允许重定向
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,

            // 设置不返回header 返回的响应就只有body
            CURLOPT_HEADER         => false,
        ];

        $curlOptions[CURLOPT_URL] = $this->buildUrl($url);

        $method = strtoupper($opts['method']);
        CurlUtil::setMethodToOption($curlOptions, $method);

        // data
        if (isset($opts['data'])) {
            $data = array_merge($opts['data'], $data);
        }
        if ($data) {
            $curlOptions[CURLOPT_POSTFIELDS] = $data;
        }

        // headers
        if ($opts['headers']) {
            $headers = array_merge($opts['headers'], $headers);
        }
        if ($headers) {
            $formatted = ClientUtil::formatHeaders($headers);

            // 首次速度非常慢 解决
            $formatted[] = 'Expect: ';
            $formatted[] = 'Accept-Encoding: gzip, deflate'; // gzip
            // set option
            $curlOptions[CURLOPT_HTTPHEADER] = $formatted;
        }

        // disable 'https' verify
        if ($opts['sslVerify'] === false) {
            /** @noinspection CurlSslServerSpoofingInspection */
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
            /** @noinspection CurlSslServerSpoofingInspection */
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
        }

        // gzip
        $curlOptions[CURLOPT_ENCODING] = 'gzip';

        // 首次速度非常慢 解决
        $curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;

        foreach ($curlOptions as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        // 如果有配置代理这里就设置代理
        if (isset($opts['proxy']) && $opts['proxy']) {
            curl_setopt($ch, CURLOPT_PROXY, $opts['proxy']['host']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $opts['proxy']['port']);
        }

        // add custom options
        if ($opts['curlOptions']) {
            curl_setopt_array($ch, $opts['curlOptions']);
        }

        return $ch;
    }

    /**
     * @param string $url
     * @param mixed|null $data
     *
     * @return string
     */
    protected function buildUrl(string $url, mixed $data = null): string
    {
        $url = trim($url);

        // is a url part.
        if ($this->baseUrl && !UrlHelper::isFullURL($url)) {
            $url = $this->baseUrl . $url;
        }

        // check again
        if (!UrlHelper::isFullURL($url)) {
            throw new RuntimeException("The request url is not full, URL $url");
        }

        if ($data) {
            return ClientUtil::buildURL($url, $data);
        }

        return $url;
    }

    /**
     * @return bool
     */
    public function isOk(): bool
    {
        return !$this->errors;
    }

    /**
     * @return bool
     */
    public function isFail(): bool
    {
        return $this->errors !== [];
    }

    /**
     * reset data
     */
    public function reset(): void
    {
        $this->mh    = null;
        $this->chMap = $this->errors = [];
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * @return array
     */
    public function getChMap(): array
    {
        return $this->chMap;
    }

    /**
     * @param array $chMap
     */
    public function setChMap(array $chMap): void
    {
        $this->chMap = $chMap;
    }

    /**
     * @return CurlMultiHandle|null
     */
    public function getMh(): ?CurlMultiHandle
    {
        return $this->mh;
    }

    /**
     * @param CurlMultiHandle $mh
     */
    public function setMh(CurlMultiHandle $mh): void
    {
        $this->mh = $mh;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
