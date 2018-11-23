<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-11-23
 * Time: 19:36
 */

namespace PhpComp\Http\Client\Traits;

/**
 * Trait TransportsByResourceTrait
 * @package PhpComp\Http\Client\Traits
 */
trait TransportsByResourceTrait
{
    /**
     * the network resource handle, it's created by:
     * fopen()
     * fsockopen()
     * stream_socket_client()
     * @var resource
     */
    protected $handle;

    /**
     * close handle
     */
    public function readAll()
    {
        \fclose($this->handle);
    }

    /**
     * close handle
     */
    public function close()
    {
        if ($this->handle) {
            \fclose($this->handle);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}