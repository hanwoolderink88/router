<?php

namespace Hanwoolderink88\Router;

class Route
{
    /**
     * @var string[]
     */
    private array $methods;

    /**
     * @var string
     */
    private string $path;

    /**
     * @var RoutePart[]|null
     */
    private ?array $routeParts = null;

    /**
     * @var bool
     */
    private bool $wildcard;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var callable
     */
    private $callable;

    /**
     * Route constructor.
     * @param string $path
     * @param string $name
     * @param string[] $methods
     * @param callable $callable
     */
    public function __construct(string $path, string $name, array $methods, callable $callable)
    {
        $this->path = rtrim(ltrim($path, '/'), '/');
        $this->name = $name ?? $this->path;
        $this->callable = $callable;
        $this->methods = $methods;
        $this->wildcard = strpos($this->path, '{') !== false;
        if ($this->wildcard === true) {
            $this->routeParts = array_map(fn($part) => new RoutePart($part), explode('/', $this->path));
        }
    }

    /**
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return callable
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }

    /**
     * @return RoutePart[]|null
     */
    public function getRouteParts(): ?array
    {
        return $this->routeParts;
    }

    /**
     * @return bool
     */
    public function hasWildcard(): bool
    {
        return $this->wildcard;
    }
}
