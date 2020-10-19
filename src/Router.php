<?php

namespace Hanwoolderink88\Router;

use Psr\Http\Message\ResponseInterface;
use ReflectionFunction;
use ReflectionMethod;

class Router
{
    /**
     * @var Route[]
     */
    private array $routes = [];

    /**
     * @param Route $route
     * @return $this
     */
    public function addRoute(Route $route): self
    {
        $this->routes[] = $route;

        return $this;
    }

    /**
     * @param string $path
     * @param string $method
     * @return ResponseInterface
     */
    public function match(string $path, string $method): ResponseInterface
    {
        $route = $this->findMatch($path, $method);
        if ($route !== null) {
            $params = $this->getFunctionParams($route);
            $wildcards = $this->getWildcardValues($path, $route->getPath());

            $p = [];
            foreach ($params as $param) {
                $isWildcardKey = $param['type'] === 'string' || $param['type'] === null;
                $isNullable = $param['nullable'];
                if ($isWildcardKey) {
                    $value = $wildcards[$param['name']] ?? null;
                    if ($value === null && $isNullable === false) {
                        // todo: throw error?
                        $value = '';
                    }
                    $p[] = $value;
                } else {
                    $p[] = null;
                }
            }

            return call_user_func_array($route->getCallable(), $p);
        }

        // todo
        return new Response('404', 404, ['X-TEST' => ['Foo']]);
    }

    /**
     * @param string $path
     * @param string $method
     * @return Route|null
     */
    private function findMatch(string $path, string $method): ?Route
    {
        // find direct match
        foreach ($this->routes as $route) {
            if ($route->getPath() === $path && in_array($method, $route->getMethods(), true)) {
                return $route;
            }
        }

        // find match with wildcard(s)
        $pathParts = explode('/', $path);
        foreach ($this->routes as $route) {
            if (strpos($route->getPath(), '{') !== false) {
                $parts = explode('/', $route->getPath());
                $matches = true;
                $i = 0;
                foreach ($parts as $part) {
                    if (strpos($part, '{') !== false) {
                        continue;
                    }

                    if ($part !== $pathParts[$i]) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches === true) {
                    return $route;
                }
            }
        }

        return null;
    }

    private function getFunctionParams(Route $route)
    {
        $callable = $route->getCallable();
        if (is_array($callable)) {
            $reflect = new ReflectionMethod($callable[0], $callable[1]);
        } else {
            $reflect = new ReflectionFunction($callable);
        }

        $fParams = $reflect->getParameters();

        $params = [];
        foreach ($fParams as $fParam) {
            $params[] = [
                'name' => $fParam->getName(),
                'type' => $fParam->getType() ? $fParam->getType()->getName() : null,
                'nullable' => $fParam->allowsNull()
            ];
        }

        return $params;
    }

    private function getWildcardValues(string $path, string $routePath)
    {
        $pathParts = explode('/', $path);
        $routeParts = explode('/', $routePath);

        $wildcards = [];
        $i = 0;
        foreach ($routeParts as $part) {
            if (strpos($part, '{') !== false) {
                $key = str_replace(['{', '}'], '', $part);
                $wildcards[$key] = $pathParts[$i];
            }
            $i++;
        }

        return $wildcards;
    }
}
