<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-08
 * Time: 16:40
 */

namespace PhpComp\Http\Client\Curl;

use PhpComp\Http\Client\AbstractClient;
use PhpComp\Http\Client\ClientUtil;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Curl
 * @package PhpComp\Http\Client\Curl
 *
 * ```
 * $curl = Curl::make([
 *   'baseUrl' =>  'http://my-site.com'
 * ]);
 * $curl->get('/users/1');
 *
 * $headers = $curl->getResponseHeaders();
 * $data = $curl->getResponseBody();
 * $array = $curl->getArrayData();
 *
 * $post = ['name' => 'john'];
 * $curl->reset()->post('/users/1', $post);
 * // $curl->reset()->byAjax()->post('/users/1', $post);
 * // $curl->reset()->byJson()->post('/users/1', json_encode($post));
 * $array = $curl->getArrayData();
 * ```
 */
class Curl extends AbstractClient implements CurlExtraInterface
{
    /**
     * Can to retry request
     * @var array
     */
    private static $canRetryErrorCodes = [
        \CURLE_COULDNT_RESOLVE_HOST,
        \CURLE_COULDNT_CONNECT,
        \CURLE_HTTP_NOT_FOUND,
        \CURLE_READ_ERROR,
        \CURLE_OPERATION_TIMEOUTED,
        \CURLE_HTTP_POST_ERROR,
        \CURLE_SSL_CONNECT_ERROR,
    ];

    /**************************************************************************
     * curl config data.
     *************************************************************************/

    /**
     * setting headers for curl
     *
     * [ 'Content-Type' => 'Content-Type: application/json' ]
     *
     * @var array
     */
    private $_headers = [];

    /**
     * @var array
     */
    private $_cookies = [];

    /**
     * setting options for curl
     * @var array
     */
    private $_curlOptions = [
        // TRUE 将 curl_exec() 获取的信息以字符串返回，而不是直接输出
        \CURLOPT_RETURNTRANSFER => true,

        // 允许重定向，最多重定向5次
        \CURLOPT_FOLLOWLOCATION => true,
        \CURLOPT_MAXREDIRS => 5,

        // true curl_exec() 会将头文件的信息作为数据流输出到响应的最前面，此时可用 [[self::parseResponse()]] 解析。
        // false curl_exec() 返回的响应就只有body data
        \CURLOPT_HEADER => true,

        // enable debug
        \CURLOPT_VERBOSE => false,

        // auto add REFERER
        \CURLOPT_AUTOREFERER => true,

        \CURLOPT_CONNECTTIMEOUT => 30,
        \CURLOPT_TIMEOUT => 30,

        // isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        \CURLOPT_USERAGENT => '5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
        //CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
    ];

    /**************************************************************************
     * response data.
     *************************************************************************/

    /**
     * @var int
     */
    private $errNo;

    /**
     * @var string
     */
    private $error;

    /**
     * The curl exec response. contains headers and body
     * @var string
     */
    private $_response;
    private $_responseParsed = false;

    /**
     * @var string body string, it's parsed from $_response
     */
    private $_responseBody = '';

    /**
     * @var string[] headers data, it's parsed from $_response
     */
    private $_responseHeaders = [];

    /**
     * The curl exec result mete info.
     * @var array
     */
    private $_responseMeta = [
        // http status code
        'status' => 200,
        'errno' => 0,
        'error' => '',
        'info' => '',
    ];

    /**
     * save request and response info, data from curl_getinfo()
     * @link https://secure.php.net/manual/zh/function.curl-getinfo.php
     * contains key:
     * "url"
     * "content_type"
     * "http_code"
     * "header_size"
     * "request_size"
     * "filetime"
     * "ssl_verify_result"
     * "redirect_count"
     * "total_time"
     * "namelookup_time"
     * "connect_time"
     * "pretransfer_time"
     * "size_upload"
     * "size_download"
     * "speed_download"
     * "speed_upload"
     * "download_content_length"
     * "upload_content_length"
     * "starttransfer_time"
     * "redirect_time"
     */
    private $_responseInfo = [];

    /**
     * {@inheritDoc}
     */
    public static function isAvailable(): bool
    {
        return \extension_loaded('curl');
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->reset();
    }

    /**
     * {@inheritDoc}
     */
    public function __construct(array $options = [])
    {
        // if 'debug = true ', is valid. will output log to the file. if is empty, output to STDERR.
        $this->defaultOptions['logFile'] = '';
        // collect curl_getinfo() data to $_responseInfo
        $this->defaultOptions['saveInfo'] = true;

        parent::__construct($options);
    }

///////////////////////////////////////////////////////////////////////
// extra
///////////////////////////////////////////////////////////////////////

    /**
     * File upload
     * @param string $url The target url
     * @param string $field The post field name
     * @param string $filePath The file path
     * @param string $mimeType The post file mime type
     * param string $postFilename The post file name
     * @return mixed
     */
    public function upload(string $url, string $field, string $filePath, string $mimeType = '')
    {
        if (!$mimeType) {
            $fInfo = \finfo_open(\FILEINFO_MIME); // 返回 mime 类型
            $mimeType = \finfo_file($fInfo, $filePath) ?: 'application/octet-stream';
        }

        // create file
        if (\function_exists('curl_file_create')) {
            $file = \curl_file_create($filePath, $mimeType); // , $postFilename
        } else {
            $this->setCurlOption(\CURLOPT_SAFE_UPLOAD, true);
            $file = "@{$filePath};type={$mimeType}"; // ;filename={$postFilename}
        }

        $headers = ['Content-Type' => 'multipart/form-data'];

        return $this->post($url, [$field => $file], $headers);
    }

    /**
     * File download and save
     * @param string $url
     * @param string $saveAs
     * @return self
     * @throws \Exception
     */
    public function download(string $url, string $saveAs)
    {
        if (($fp = \fopen($saveAs, 'wb')) === false) {
            throw new \RuntimeException('Failed to save the content', __LINE__);
        }

        $data = $this->request($url);

        \fwrite($fp, $data);
        \fclose($fp);

        return $this;
    }

    /**
     * Image file download and save
     * @param string $imgUrl image url e.g. http://static.oschina.net/uploads/user/277/554046_50.jpg
     * @param string $saveDir 图片保存路径
     * @param string $rename 图片重命名(只写名称，不用后缀) 为空则使用原名称
     * @return string
     */
    public function downloadImage(string $imgUrl, string $saveDir, string $rename = '')
    {
        // e.g. http://static.oschina.net/uploads/user/277/554046_50.jpg?t=34512323
        if (\strpos($imgUrl, '?')) {
            [$real,] = \explode('?', $imgUrl, 2);
        } else {
            $real = $imgUrl;
        }

        $last = \trim(\strrchr($real, '/'), '/');

        // special url e.g http://img.blog.csdn.net/20150929103749499
        if (false === \strpos($last, '.')) {
            $suffix = '.jpg';
            $name = $rename ?: $last;
        } else {
            $info = \pathinfo($real, PATHINFO_EXTENSION | PATHINFO_FILENAME);
            $suffix = $info['extension'] ?: '.jpg';
            $name = $rename ?: $info['filename'];
        }

        $imgFile = $saveDir . '/' . $name . $suffix;
        if (\file_exists($imgFile)) {
            return $imgFile;
        }

        // set Referrer
        $this->setReferrer('http://www.baidu.com');
        $imgData = $this->request($imgUrl)->getResponseBody();

        \file_put_contents($imgFile, $imgData);

        return $imgFile;
    }

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        foreach ($request->getHeaders() as $name => $values) {
            $this->setHeader($name, \implode(', ', $values));
        }

        // send request
        $this->request($request->getRequestTarget(), $request->getBody(), $request->getMethod());

        // create response instance.
        $psr7res = $this->createPsr7Response();

        // write body data
        $psr7res->getBody()->write($this->getResponseBody());

        // with status
        $psr7res = $psr7res->withStatus($this->getHttpCode());

        // add headers
        foreach ($this->getResponseHeaders() as $name => $value) {
            $psr7res = $psr7res->withHeader($name, $value);
        }

        return $psr7res;
    }

    /**
     * Send request
     * @param string $url
     * @param mixed $data
     * @param string $method
     * @param array $headers
     * @param array $options
     * @return $this
     */
    public function request(string $url, $data = null, string $method = 'GET', array $headers = [], array $options = [])
    {
        $method = \strtoupper($method);

        // if ($method) {
        //     $options['method'] = $method;
        // }

        if (!isset(self::$supportedMethods[$method])) {
            throw new \InvalidArgumentException("The method type [$method] is not supported!");
        }

        // parse $options

        // collect headers
        if (isset($options['headers'])) {
            $headers = \array_merge($options['headers'], $headers);
        }

        // set headers
        $this->setHeaders($headers);

        // get request url
        $url = $this->buildUrl($url);

        // init curl
        $ch = \curl_init();
        $this->prepareRequest($ch, $headers, $options);

        // add send data
        if ($data) {
            // allow post data
            if (self::$supportedMethods[$method]) {
                \curl_setopt($ch, \CURLOPT_POSTFIELDS, $data);
            } else {
                $url = ClientUtil::buildURL($url, $data);
            }
        }

        \curl_setopt($ch, \CURLOPT_URL, ClientUtil::encodeURL($url));

        $response = '';
        $retries = (int)$this->options['retry'];

        // execute
        while ($retries >= 0) {
            if (false === ($response = \curl_exec($ch))) {
                $curlErrNo = \curl_errno($ch);

                if (false === \in_array($curlErrNo, self::$canRetryErrorCodes, true)) {
                    $curlError = \curl_error($ch);

                    $this->errNo = $curlErrNo;
                    $this->error = \sprintf('Curl error (code %s): %s', $this->errNo, $curlError);
                }

                $retries--;
                continue;
            }
            break;
        }

        // save request and response info
        if ($this->options['saveInfo']) {
            $this->_responseInfo = \curl_getinfo($ch);
        }

        $this->_response = $response;

        // close resource
        \curl_close($ch);
        return $this;
    }

///////////////////////////////////////////////////////////////////////
//   helper method
///////////////////////////////////////////////////////////////////////

    /**
     * @param resource $ch
     * @param array $headers
     * @param array $opts
     */
    protected function prepareRequest($ch, array $headers = [], array $opts = [])
    {
        $this->resetResponse();

        // open debug
        if ($this->isDebug()) {
            $this->_curlOptions[\CURLOPT_VERBOSE] = true;

            // redirect exec log to logFile.
            if ($logFile = $this->options['logFile']) {
                $this->_curlOptions[\CURLOPT_STDERR] = $logFile;
            }
        }

        // set options, can not use `array_merge()`, $options key is int.

        // merge default options
        // $this->_curlOptions = ClientUtil::mergeArray($this->defaultOptions, $this->_curlOptions);
        $this->_curlOptions = ClientUtil::mergeArray($this->_curlOptions, $opts);

        // append http headers to options
        if ($this->_headers) {
            $options[\CURLOPT_HTTPHEADER] = $this->formatHeaders();
        }

        // append http cookies to options
        if ($this->_cookies) {
            $options[\CURLOPT_COOKIE] = \http_build_query($this->_cookies, '', '; ');
        }

        \curl_setopt_array($ch, $this->_curlOptions);
    }

    protected function parseResponse()
    {
        // have been parsed || no response data
        if ($this->_responseParsed || !($response = $this->_response)) {
            return false;
        }

        // if no return headers data
        if (false === $this->getOption(\CURLOPT_HEADER, false)) {
            $this->_responseBody = $response;
            $this->_responseParsed = true;
            return true;
        }

        # Headers regex
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        # Extract headers from response
        \preg_match_all($pattern, $response, $matches);
        $headers_string = array_pop($matches[0]);
        $headers = \explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

        # Include all received headers in the $headers_string
        while (\count($matches[0])) {
            $headers_string = \array_pop($matches[0]) . $headers_string;
        }

        # Remove all headers from the response body
        $this->_responseBody = \str_replace($headers_string, '', $response);

        # Extract the version and status from the first header
        $versionAndStatus = \array_shift($headers);

        \preg_match_all('#HTTP/(\d\.\d)\s((\d\d\d)\s((.*?)(?=HTTP)|.*))#', $versionAndStatus, $matches);

        $this->_responseHeaders['Http-Version'] = \array_pop($matches[1]);
        $this->_responseHeaders['Status-Code'] = \array_pop($matches[3]);
        $this->_responseHeaders['Status'] = \array_pop($matches[2]);

        # Convert headers into an associative array
        foreach ($headers as $header) {
            \preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            $this->_responseHeaders[$matches[1]] = $matches[2];
        }

        $this->_responseParsed = true;
        return true;
    }

    /**
     * @param string $url
     * @param mixed $data
     * @return string
     */
    protected function buildUrl(string $url, $data = null)
    {
        $url = \trim($url);

        // is a url part.
        if ($this->baseUrl && !ClientUtil::isFullURL($url)) {
            $url = $this->baseUrl . $url;
        }

        // check again
        if (!ClientUtil::isFullURL($url)) {
            throw new \RuntimeException("The request url is not full, URL $url");
        }

        if ($data) {
            return ClientUtil::buildURL($url, $data);
        }

        return $url;
    }

///////////////////////////////////////////////////////////////////////
//   response data
///////////////////////////////////////////////////////////////////////

    /**
     * @return bool
     */
    public function isOk(): bool
    {
        return !$this->error;
    }

    /**
     * @return bool
     */
    public function isFail(): bool
    {
        return (bool)$this->error;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->_responseInfo['http_code'] ?? 200;
    }

    /**
     * @return int
     */
    public function getConnectTime(): int
    {
        return $this->_responseInfo['connect_time'] ?? 0;
    }

    /**
     * @return int
     */
    public function getTotalTime(): int
    {
        return $this->_responseInfo['total_time'] ?? 0;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @param null|string $key
     * @return array|mixed|null
     */
    public function getMeta($key = null)
    {
        return $this->getResponseMeta($key);
    }

    /**
     * @param string|null $key
     * @return array|mixed|null
     */
    public function getResponseMeta(string $key = null)
    {
        if ($key) {
            return $this->_responseMeta[$key] ?? null;
        }

        return $this->_responseMeta;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getResponseBody();
    }

    /**
     * @return string
     */
    public function getResponseBody()
    {
        $this->parseResponse();

        return $this->_responseBody;
    }

    /**
     * @return bool|array
     */
    public function getArrayData()
    {
        return $this->getJsonArray();
    }

    /**
     * @return bool|array
     */
    public function getJsonArray()
    {
        if (!$this->getResponseBody()) {
            return [];
        }

        $data = \json_decode($this->_responseBody, true);
        if (\json_last_error() > 0) {
            return false;
        }

        return $data;
    }

    /**
     * @return bool|\stdClass
     */
    public function getJsonObject()
    {
        if (!$this->getResponseBody()) {
            return false;
        }

        $data = \json_decode($this->_responseBody);
        if (\json_last_error() > 0) {
            return false;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getResponseHeaders()
    {
        $this->parseResponse();

        return $this->_responseHeaders;
    }

    /**
     * @param string $name
     * @param null $default
     * @return string
     */
    public function getResponseHeader($name, $default = null)
    {
        $this->parseResponse();

        return $this->_responseHeaders[$name] ?? $default;
    }

    /**
     * @return int
     */
    public function getErrNo(): int
    {
        return $this->errNo;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Was an 'info' header returned.
     */
    public function isInfo(): bool
    {
        return $this->_responseMeta['status'] >= 100 && $this->_responseMeta['status'] < 200;
    }

    /**
     * Was an 'OK' response returned.
     */
    public function isSuccess(): bool
    {
        return $this->_responseMeta['status'] >= 200 && $this->_responseMeta['status'] < 300;
    }

    /**
     * Was a 'redirect' returned.
     */
    public function isRedirect(): bool
    {
        return $this->_responseMeta['status'] >= 300 && $this->_responseMeta['status'] < 400;
    }

    /**
     * Was an 'error' returned (client error or server error).
     */
    public function isError()
    {
        return $this->_responseMeta['status'] >= 400 && $this->_responseMeta['status'] < 600;
    }

///////////////////////////////////////////////////////////////////////
//   reset data/unset attribute
///////////////////////////////////////////////////////////////////////

    /**
     * @return $this
     */
    public function resetHeaders()
    {
        $this->_headers = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function resetCookies()
    {
        $this->_cookies = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function resetOptions()
    {
        $this->_curlOptions = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function resetResponse()
    {
        $this->_response = $this->_responseBody = null;
        $this->_responseParsed = false;
        $this->_responseInfo = $this->_responseHeaders = [];
        $this->_responseMeta = [
            // http status code
            'status' => 200,
            'errno' => 0,
            'error' => '',
            'info' => '',
        ];

        return $this;
    }

    /**
     * Reset the last time headers,cookies,options,response data.
     * @return $this
     */
    public function reset()
    {
        $this->_headers = $this->_curlOptions = $this->_cookies = [];

        return $this->resetResponse();
    }

///////////////////////////////////////////////////////////////////////
//   request cookies
///////////////////////////////////////////////////////////////////////

    /**
     * Set contents of HTTP Cookie header.
     * @param string $key The name of the cookie
     * @param string $value The value for the provided cookie name
     * @return $this
     */
    public function setCookie($key, $value)
    {
        $this->_cookies[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->_cookies;
    }

    /**************************************************************************
     * request headers
     *************************************************************************/

    /**
     * @return $this
     */
    public function byJson()
    {
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');

        return $this;
    }

    /**
     * @return Curl
     */
    public function byXhr()
    {
        return $this->byAjax();
    }

    /**
     * @return $this
     */
    public function byAjax()
    {
        $this->setHeader('X-Requested-With', 'XMLHttpRequest');

        return $this;
    }

    /**
     * get Headers
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->_headers;
    }

    /**
     * @return array
     */
    public function formatHeaders(): array
    {
        $formatted = [];
        foreach ($this->_headers as $name => $value) {
            $name = \ucwords($name);
            $formatted[] = "$name: $value";
        }

        return $formatted;
    }

    /**
     * set Headers
     * @inheritdoc
     */
    public function setHeaders(array $headers)
    {
        $this->_headers = []; // clear old.

        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value, true);
        }

        return $this;
    }

    /**
     * @param array $headers
     * @param bool $override
     * @return $this
     */
    public function addHeaders(array $headers, bool $override = true)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value, $override);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool $override
     * @return $this
     */
    public function setHeader(string $name, string $value, bool $override = false)
    {
        if ($override || !isset($this->_headers[$name])) {
            $this->_headers[$name] = \ucwords($name) . ": $value";
        }

        return $this;
    }

    /**
     * @param string|array $name
     * @return $this
     */
    public function delHeader(string $name)
    {
        foreach ((array)$name as $item) {
            if (isset($this->_headers[$item])) {
                unset($this->_headers[$item]);
            }
        }

        return $this;
    }

    /**************************************************************************
     * set curl options
     *************************************************************************/

    /**
     * @param string $userAgent
     * @return $this
     */
    public function setUserAgent(string $userAgent)
    {
        $this->_curlOptions[CURLOPT_USERAGENT] = $userAgent;

        return $this;
    }

    /**
     * @param string $referrer
     * @return $this
     */
    public function setReferrer(string $referrer)
    {
        $this->_curlOptions[\CURLOPT_REFERER] = $referrer;
        return $this;
    }

    /**
     * @param string $host
     * @param int $port
     * @return $this
     */
    public function setProxy(string $host, int $port)
    {
        $this->_curlOptions[\CURLOPT_PROXY] = $host;
        $this->_curlOptions[\CURLOPT_PROXYPORT] = $port;

        return $this;
    }

    /**
     * Use http auth
     * @param string $user
     * @param string $pwd
     * @param int $authType CURLAUTH_BASIC CURLAUTH_DIGEST
     * @return $this
     */
    public function setUserAuth(string $user, string $pwd = '', int $authType = CURLAUTH_BASIC)
    {
        $this->_curlOptions[\CURLOPT_HTTPAUTH] = $authType;
        $this->_curlOptions[\CURLOPT_USERPWD] = "$user:$pwd";

        return $this;
    }

    /**
     * Use SSL certificate/private-key auth
     *
     * @param string $pwd The SLL CERT/KEY password
     * @param string $file The SLL CERT/KEY file
     * @param string $authType The auth type: 'cert' or 'key'
     * @return $this
     */
    public function setSSLAuth(string $pwd, string $file, string $authType = self::SSL_TYPE_CERT)
    {
        if ($authType !== self::SSL_TYPE_CERT && $authType !== self::SSL_TYPE_KEY) {
            throw new \InvalidArgumentException('The SSL auth type only allow: cert|key');
        }

        if (!\file_exists($file)) {
            $name = $authType === self::SSL_TYPE_CERT ? 'certificate' : 'private key';
            throw new \InvalidArgumentException("The SSL $name file not found: {$file}");
        }

        if ($authType === self::SSL_TYPE_CERT) {
            $this->_curlOptions[\CURLOPT_SSLCERTPASSWD] = $pwd;
            $this->_curlOptions[\CURLOPT_SSLCERT] = $file;
        } else {
            $this->_curlOptions[\CURLOPT_SSLKEYPASSWD] = $pwd;
            $this->_curlOptions[\CURLOPT_SSLKEY] = $file;
        }

        return $this;
    }

    /**
     * disable 'https' verify
     * @return $this
     */
    public function disableHTTPSVerify()
    {
        $this->_curlOptions[\CURLOPT_SSL_VERIFYPEER] = false;
        $this->_curlOptions[\CURLOPT_SSL_VERIFYHOST] = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCurlOptions(array $options)
    {
        $this->_curlOptions = array_merge($this->_curlOptions, $options);

        return $this;
    }

    /**
     * @param int $name \CURLOPT_*
     * @param $value
     * @return $this
     */
    public function setCurlOption($name, $value)
    {
        $this->_curlOptions[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getCurlOptions(): array
    {
        return $this->_curlOptions;
    }

    /**
     * @param int|string $name
     * @param bool $default
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return $this->_curlOptions[$name] ?? $default;
    }

}
