<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-23
 * Time: 17:14
 */

namespace PhpComp\Http\Client\Traits;

/**
 * Trait ParseRawResponseTrait
 * @package PhpComp\Http\Client\Traits
 */
trait ParseRawResponseTrait
{
    /**
     * The curl exec response data string. contains headers and body
     * @var string
     */
    private $rawResponse = '';
    private $responseParsed = false;

    /**
     * parse response data string.
     */
    protected function parseResponse()
    {
        // has been parsed || empty response data
        if ($this->responseParsed || '' === $this->rawResponse) {
            return;
        }

        $response = $this->rawResponse;

        # Headers regex
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        # Extract headers from response
        \preg_match_all($pattern, $response, $matches);
        $headersString = \array_pop($matches[0]);
        $headers = \explode("\r\n", \str_replace("\r\n\r\n", '', $headersString));

        // parse headers
        $this->parseResponseHeaders($headers);

        # Include all received headers in the $headers_string
        while (\count($matches[0])) {
            $headersString = \array_pop($matches[0]) . $headersString;
        }

        # Remove all headers from the response body
        $this->responseBody = \str_replace($headersString, '', $response);

        $this->rawResponse = '';
        $this->responseParsed = true;
    }

    /**
     * @param array $headers
     * [
     *  "HTTP/1.0 200 OK",
     *  "Accept-Ranges: bytes"
     *  "Cache-Control: no-cache"
     *  "Content-Length: 14615"
     *  "Content-Type: text/html"
     *  ...
     * ]
     */
    protected function parseResponseHeaders(array &$headers)
    {
        # Extract the version and status from the first header
        $versionAndStatus = \array_shift($headers);

        \preg_match_all('#HTTP/(\d\.\d)\s((\d\d\d)\s((.*?)(?=HTTP)|.*))#', $versionAndStatus, $matches);

        // '1.1' 200 '200 OK'
        $this->responseHeaders['Http-Version'] = \array_pop($matches[1]);

        $statusCode = \array_pop($matches[3]);
        if ($this->statusCode === 0) {
            $this->statusCode = (int)$statusCode;
        }

        $this->responseHeaders['Status-Msg'] = \array_pop($matches[2]);

        # Convert headers into an associative array
        foreach ($headers as $header) {
            // \preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            // $this->responseHeaders[$matches[1]] = $matches[2];
            list($name, $value) = \explode(': ', $header);
            $name = \ucwords($name);
            $this->responseHeaders[$name] = $value;
        }
    }

    /**
     * @param string $rawResponse
     */
    protected function setRawResponse(string $rawResponse)
    {
        $this->rawResponse = $rawResponse;
    }

    /**
     * @param bool $responseParsed
     */
    protected function setResponseParsed(bool $responseParsed)
    {
        $this->responseParsed = $responseParsed;
    }

}