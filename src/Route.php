<?php
declare(strict_types=1);

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
     * @var string|string[]|callable
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
     * @param string[] $params
     * @return string
     * @throws RouterMatchException
     */
    public function getPathFilledIn(array $params): string
    {
        if ($this->hasWildcard()) {
            $uriParts = [];
            $parts = $this->getRouteParts();
            foreach ($parts as $part) {
                if ($part->isWildcard()) {
                    $value = $params[$part->getString()] ?? null;
                    if ($value === null) {
                        throw new RouterMatchException("Redirect expects parameter {$part->getString()}");
                    }
                    $uriParts[] = $value;
                } else {
                    $uriParts[] = $part->getString();
                }
            }
            $uri = implode('/', $uriParts);
        } else {
            $uri = $this->getPath();
        }

        return $uri;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|string[]|callable
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
