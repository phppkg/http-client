<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

namespace PhpComp\Http\Client;

use PhpComp\Http\Client\Exception\ClientException;
use PhpComp\Http\Client\Traits\ParseRawResponseTrait;
use PhpComp\Http\Client\Traits\StreamContextBuildTrait;
use Throwable;
use Toolkit\Stdlib\Str\UrlHelper;
use function array_merge;
use function file_get_contents;
use function function_exists;
use function strtoupper;

/**
 * Class FileClient - powered by func file_get_contents()
 *
 * @package PhpComp\Http\Client
 */
class FileClient extends AbstractClient
{
    use StreamContextBuildTrait, ParseRawResponseTrait;

    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return function_exists('file_get_contents');
    }

    /**
     * {@inheritdoc}
     */
    public function request(
        string $url,
        $data = null,
        string $method = self::GET,
        array $headers = [],
        array $options = []
    ): ClientInterface {
        if ($method) {
            $options['method'] = strtoupper($method);
        }

        // get request url info
        $url = $this->buildFullUrl($url);

        // merge global options data.
        $options = array_merge($this->options, $options);

        try {
            $reqCtx  = $this->buildStreamContext($url, $headers, $options, $data);
            $fullUrl = UrlHelper::encode2($this->fullUrl);

            // send request
            $this->responseBody = file_get_contents($fullUrl, false, $reqCtx);

            // false is failure
            if ($this->responseBody === false) {
                $this->responseBody = '';
            }
        } catch (Throwable $e) {
            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }

        /**
         * collect headers data
         * $http_response_header will auto save HTTP response headers data.
         *
         * @see https://secure.php.net/manual/zh/reserved.variables.httpresponseheader.php
         */
        if ($http_response_header !== null) {
            $this->parseResponseHeaders($http_response_header);
        }

        return $this;
    }
}
