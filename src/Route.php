<?php

namespace Hanwoolderink88\Router;

class Route
{
    /**
     * @var string[]
     */
    protected array $methods;

    /**
     * @var string
     */
    protected string $path;

    /**
     * @var RoutePart[]
     */
    protected array $routeParts = [];

    /**
     * @var bool
     */
    protected bool $wildcard;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var mixed
     */
    protected $callable;

    /**
     * Route constructor.
     * @param string $path
     * @param string $name
     * @param string[] $methods
     * @param mixed $callable
     */
    public function __construct(string $path, string $name, array $methods, $callable)
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
     * @return mixed
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @return RoutePart[]
     */
    public function getRouteParts(): array
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
