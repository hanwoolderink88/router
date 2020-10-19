<?php

namespace Hanwoolderink88\Router;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    private const ALLOWED_PROTOCOL_VERSIONS = ['0.9', '1.0', '1.1'];

    /**
     * @var string
     */
    private string $protocolVersion = '1.1';

    /**
     * @var string[][]
     */
    private array $headers = [];

    /**
     * @var StreamInterface
     */
    private StreamInterface $body;

    /**
     * @var int
     */
    private int $statusCode = 200;

    /**
     * @var string
     */
    private string $reasonPhrase = '';

    /**
     * @param mixed $body
     * @param int $statusCode
     * @param string[][] $headers
     */
    public function __construct($body, int $statusCode = 200, array $headers = [])
    {
        if ($body instanceof StreamInterface) {
            $this->setBody($body);
        } else {
            $this->setBody(new ResponseBody($body));
        }

        $this->setStatusCode($statusCode);

        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @param string $version
     * @return Response
     * @throws Exception
     */
    public function withProtocolVersion($version)
    {
        if (!in_array($version, self::ALLOWED_PROTOCOL_VERSIONS, true)) {
            throw new Exception('version is not allowed');
        }

        $inst = clone $this;
        $inst->protocolVersion = $version;

        return $inst;
    }

    /**
     * @return string[][]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * @param string $name
     * @return array|string[]
     */
    public function getHeader($name)
    {
        return $this->hasHeader($name) ? $this->headers[$name] : [];
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name)
    {
        $headers = $this->getHeader($name);

        return implode(',', $headers);
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @return Response
     */
    public function withHeader($name, $value)
    {
        // create an array if the input is a string|number
        if (!is_array($value)) {
            $value = [$value];
        }

        // string cast all values
        $value = array_map(fn($i) => (string)$i, $value);

        $inst = clone $this;
        $inst->headers[$name] = $value;

        return $inst;
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @return Response
     */
    public function withAddedHeader($name, $value)
    {
        // create an array if the input is a string|number
        if (!is_array($value)) {
            $value = [$value];
        }

        // string cast all values
        $value = array_map(fn($i) => (string)$i, $value);

        $inst = clone $this;
        $inst->headers[$name] = $value;

        return $inst;
    }

    /**
     * @param string $name
     * @return Response
     */
    public function withoutHeader($name)
    {
        $inst = clone $this;
        if (isset($inst->headers[$name])) {
            unset($inst->headers[$name]);
        }

        return $inst;
    }

    /**
     * @param StreamInterface $body
     * @return Response
     */
    public function setBody(StreamInterface $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return StreamInterface
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param StreamInterface $body
     * @return Response
     */
    public function withBody(StreamInterface $body)
    {
        $inst = clone $this;
        $inst->body = $body;

        return $inst;
    }

    /**
     * @param int $code
     * @return self
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int $code
     * @param string $reasonPhrase
     * @return Response|void
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $inst = clone $this;
        $inst->statusCode = (int)$code;
        $inst->reasonPhrase = (string)$reasonPhrase;

        return $inst;
    }

    /**
     * @param string $reason
     * @return self
     */
    public function setReasonPhrase(string $reason): self
    {
        $this->reasonPhrase = $reason;

        return $this;
    }

    /**
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
}
