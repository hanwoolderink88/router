<?php

namespace Hanwoolderink88\Router;

class RoutePart
{
    /**
     * @var string
     */
    protected string $string;

    /**
     * @var bool
     */
    protected bool $isWildcard = false;

    /**
     * RoutePart constructor.
     * @param string $part
     */
    public function __construct(string $part)
    {
        if (strpos($part, '{') !== false) {
            $this->isWildcard = true;
            $this->string = (string)str_replace(['{', '}'], '', $part);
        } else {
            $this->string = $part;
        }
    }

    /**
     * @return string|string[]
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * @return bool
     */
    public function isWildcard(): bool
    {
        return $this->isWildcard;
    }
}
