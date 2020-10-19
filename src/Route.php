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
        $this->path = $path;
        $this->name = $name ?? $path;
        $this->callable = $callable;
        $this->methods = $methods;
    }

    /**
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param string[] $methods
     * @return $this
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return Route
     */
    public function setPath(string $path): Route
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Route
     */
    public function setName(string $name): Route
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return callable
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }

    /**
     * @param callable $callable
     * @return Route
     */
    public function setCallable(callable $callable): Route
    {
        $this->callable = $callable;

        return $this;
    }
}
