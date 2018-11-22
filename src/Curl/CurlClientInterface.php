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
interface CurlClientInterface
{
    /**
     * Set curl options
     * @param array $options
     * @return $this
     */
    public function setCurlOptions(array $options);

    public function getCurlOptions(): array ;
}
