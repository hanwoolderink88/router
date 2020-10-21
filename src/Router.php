<?php

namespace Hanwoolderink88\Router;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use function in_array;

class Router
{
    /**
     * @var Route[]
     */
    private array $routes = [];

    /**
     * @var ResponseInterface|null
     */
    private ?ResponseInterface $response404 = null;

    /**
     * @var ContainerInterface|null
     */
    private ?ContainerInterface $container;

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param Route[] $routes
     * @return Router
     */
    public function setRoutes(array $routes): Router
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse404(): ?ResponseInterface
    {
        return $this->response404;
    }

    /**
     * @param ResponseInterface $response404
     * @return Router
     */
    public function setResponse404(ResponseInterface $response404): Router
    {
        $this->response404 = $response404;

        return $this;
    }

    /**
     * @return ContainerInterface|null
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(?ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @param Route $route
     * @return $this
     * @throws RouterAddRouteException
     */
    public function addRoute(Route $route): self
    {
        $i = 0;
        foreach ($this->routes as $registeredRoute) {
            $sharedMethods = array_intersect($route->getMethods(), $registeredRoute->getMethods());
            if ($registeredRoute->getPath() === $route->getPath() && count($sharedMethods) > 0) {
                throw new RouterAddRouteException("route with path \"{$route->getPath()}}\" already exists");
            }

            // overwrite/remove routes with the same name
            if ($registeredRoute->getName() === $route->getName()) {
                unset($this->routes[$i]);
            }

            $i++;
        }

        $this->routes[] = $route;

        return $this;
    }

    /**
     * @param string $path
     * @param string $method
     * @return ResponseInterface
     * @throws ReflectionException|RouterMatchException
     */
    public function match(string $path, string $method): ResponseInterface
    {
        // standardise path
        $path = rtrim(ltrim($path, '/'), '/');
        $pathParts = explode('/', $path);

        // Find a matching route
        $route = $this->findMatch($path, $pathParts, $method);

        // return a 404 if the route is not found
        if ($route === null) {
            $response404 = $this->getResponse404();
            if ($response404 === null) {
                throw new RouterMatchException('No 404 route is specified in the router');
            }

            return $response404;
        }

        // Param matching for wildcards and DI
        $params = $this->matchParams($route, $pathParts);

        // call the callback function of the matched route with the
        return call_user_func_array($route->getCallable(), $params);
    }

    /**
     * @param string $path
     * @param string[] $pathParts
     * @param string $method
     * @return Route|null
     */
    private function findMatch(string $path, array $pathParts, string $method): ?Route
    {
        // find direct match
        foreach ($this->routes as $route) {
            if ($route->getPath() === $path && in_array($method, $route->getMethods(), true)) {
                return $route;
            }
        }

        // find match with wildcard(s)
        foreach ($this->routes as $route) {
            if ($route->hasWildcard() && in_array($method, $route->getMethods(), true)) {
                $parts = $route->getRouteParts();
                $matches = true;
                $i = 0;
                foreach ($pathParts as $pathPart) {
                    $part = $parts[$i] ?? null;
                    $i++;

                    //
                    if ($part === null) {
                        $matches = false;
                        break;
                    }

                    // if the part is a wildcard it does not have to match
                    if ($part->isWildcard()) {
                        continue;
                    }

                    // if the part is not a wildcard is does have to match
                    if ($part->getString() !== $pathPart) {
                        $matches = false;
                        break;
                    }
                }

                // if the boolean is still on true we have a match
                if ($matches === true) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * @param Route $route
     * @param string[] $pathParts
     * @return mixed[]
     * @throws ReflectionException
     * @throws RouterMatchException
     */
    private function matchParams(Route $route, array $pathParts): array
    {
        $params = $this->getFunctionParams($route);
        $wildcards = $route->hasWildcard() ? $this->getWildcardValues($pathParts, $route->getRouteParts()) : [];

        $p = [];
        foreach ($params as $param) {
            $isWildcardParam = ($param['type'] === 'string' || $param['type'] === null);
            $isNullable = $param['nullable'];
            $value = null;

            if ($isWildcardParam) {
                // a wildcard param is defined in the route path by /{name}
                $value = $wildcards[$param['name']] ?? null;
            } elseif ($this->container !== null && $this->container->has($param['type'])) {
                // (johnny) Dep inject
                $value = $this->container->get($param['type']);
            }

            if ($value === null && $isNullable === false) {
                $name = $param['name'];
                $msg = "Callback function has argument with name \"{$name}\" but no wildcard or DI service was found";
                throw new RouterMatchException($msg);
            }

            $p[] = $value;
        }

        return $p;
    }

    /**
     * @param Route $route
     * @return mixed[][]
     * @throws ReflectionException
     */
    private function getFunctionParams(Route $route): array
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
                /** @phpstan-ignore-next-line */
                'type' => $fParam->getType() ? $fParam->getType()->getName() : null,
                'nullable' => $fParam->allowsNull()
            ];
        }

        return $params;
    }

    /**
     * @param string[] $pathParts
     * @param RoutePart[] $routeParts
     * @return string[]
     */
    private function getWildcardValues(array $pathParts, array $routeParts): array
    {
        $wildcards = [];
        $i = 0;
        foreach ($routeParts as $part) {
            if ($part->isWildcard()) {
                $wildcards[$part->getString()] = $pathParts[$i];
            }
            $i++;
        }

        return $wildcards;
    }
}
