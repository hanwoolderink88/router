<?php

namespace Hanwoolderink88\Router;

class RoutePart
{
    /**
     * @var string
     */
    private string $string;

    /**
     * @var bool
     */
    private bool $isWildcard = false;

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
     * @param string|string[] $string
     * @return RoutePart
     */
    public function setString($string)
    {
        $this->string = $string;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWildcard(): bool
    {
        return $this->isWildcard;
    }

    /**
     * @param bool $isWildcard
     * @return RoutePart
     */
    public function setIsWildcard(bool $isWildcard): RoutePart
    {
        $this->isWildcard = $isWildcard;

        return $this;
    }
}
