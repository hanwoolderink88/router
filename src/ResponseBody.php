<?php

namespace Hanwoolderink88\Router;

use Exception;
use Psr\Http\Message\StreamInterface;

class ResponseBody implements StreamInterface
{
    /**
     * @var mixed
     */
    private $body;

    /**
     * @param mixed $body
     */
    public function __construct($body)
    {
        $this->body = $body;
    }

    /**
     * @return false|mixed|string
     * @throws Exception
     */
    public function __toString()
    {
        if (is_string($this->body)) {
            return $this->body;
        } elseif (is_numeric($this->body)) {
            return (string)$this->body;
        } elseif (is_array($this->body)) {
            return json_encode($this->body);
        }

        throw new Exception('Cannot parse the body to a string');
    }

    public function close()
    {
        // TODO: Implement close() method.
    }

    public function detach()
    {
        // TODO: Implement detach() method.
    }

    public function getSize()
    {
        // TODO: Implement getSize() method.
    }

    public function tell()
    {
        // TODO: Implement tell() method.
    }

    public function eof()
    {
        // TODO: Implement eof() method.
    }

    public function isSeekable()
    {
        // TODO: Implement isSeekable() method.
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        // TODO: Implement seek() method.
    }

    public function rewind()
    {
        // TODO: Implement rewind() method.
    }

    public function isWritable()
    {
        // TODO: Implement isWritable() method.
    }

    public function write($string)
    {
        // TODO: Implement write() method.
    }

    public function isReadable()
    {
        // TODO: Implement isReadable() method.
    }

    public function read($length)
    {
        // TODO: Implement read() method.
    }

    public function getContents()
    {
        // TODO: Implement getContents() method.
    }

    public function getMetadata($key = null)
    {
        // TODO: Implement getMetadata() method.
    }
}
