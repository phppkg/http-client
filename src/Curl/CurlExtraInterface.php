<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-09
 * Time: 13:14
 */

namespace PhpComp\Http\Client\Curl;

/**
 * Class CurlExtraInterface
 * @package PhpComp\Http\Client\Curl
 */
interface CurlExtraInterface
{
    // ssl auth type
    const SSL_TYPE_CERT = 'cert';
    const SSL_TYPE_KEY = 'key';

    public function reset();

    /**
     * Reset response data
     * @return self
     */
    public function resetResponse();

    /**
     * set Headers
     *
     * [
     *  'Content-Type' => 'application/json'
     * ]
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers);

    /**
     * add headers
     * @param array $headers
     * @param bool $override $override Override exists
     * @return mixed
     */
    public function addHeaders(array $headers, bool $override = true);

    /**
     * @return mixed
     */
    public function getHeaders(): array ;

    /**
     * Set curl options
     * @param array $options
     * @return $this
     */
    public function setCurlOptions(array $options);

    public function getCurlOptions(): array ;
}
