<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-23
 * Time: 17:14
 */

namespace PhpComp\Http\Client;

/**
 * Trait RawResponseParserTrait
 * @package PhpComp\Http\Client
 */
trait RawResponseParserTrait
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
        $headers_string = array_pop($matches[0]);
        $headers = \explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

        # Include all received headers in the $headers_string
        while (\count($matches[0])) {
            $headers_string = \array_pop($matches[0]) . $headers_string;
        }

        # Remove all headers from the response body
        $this->responseBody = \str_replace($headers_string, '', $response);

        # Extract the version and status from the first header
        $versionAndStatus = \array_shift($headers);

        \preg_match_all('#HTTP/(\d\.\d)\s((\d\d\d)\s((.*?)(?=HTTP)|.*))#', $versionAndStatus, $matches);

        // '1.1' 200 '200 OK'
        $this->responseHeaders['Http-Version'] = \array_pop($matches[1]);

        $statusCode = \array_pop($matches[3]);
        if ($this->statusCode === 0) {
            $this->statusCode = $statusCode;
        }

        $this->responseHeaders['Status-Msg'] = \array_pop($matches[2]);

        # Convert headers into an associative array
        foreach ($headers as $header) {
            \preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            $this->responseHeaders[$matches[1]] = $matches[2];
        }

        $this->rawResponse = '';
        $this->responseParsed = true;
    }

    /**
     * @param string $rawResponse
     */
    public function setRawResponse(string $rawResponse)
    {
        $this->rawResponse = $rawResponse;
    }
}