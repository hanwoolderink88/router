<?php
declare(strict_types=1);

namespace Hanwoolderink88\Router;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use function in_array;

class Router implements RequestHandlerInterface
{
    /**
     * @var RouteHandler
     */
    protected RouteHandler $routeHandler;

    /**
     * @var ResponseInterface|null
     */
    protected ?ResponseInterface $response404 = null;

    /**
     * @var ContainerInterface|null
     */
    protected ?ContainerInterface $container;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->routeHandler = new RouteHandler();
    }

    /**
     * @return RouteHandler
     */
    public function getRouteHandler(): RouteHandler
    {
        return $this->routeHandler;
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
        return $this->container ?? null;
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(?ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ReflectionException
     * @throws RouterMatchException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // standardise path
        $path = rtrim(ltrim($request->getUri()->getPath(), '/'), '/');
        $pathParts = explode('/', $path);

        // Find a matching route
        $route = $this->findMatch($path, $pathParts, $request->getMethod());

        // return a 404 if the route is not found
        if ($route === null) {
            $response404 = $this->getResponse404();
            if ($response404 === null) {
                throw new RouterMatchException('No 404 response is specified in the router');
            }

            return $response404;
        }

        // Param matching for wildcards and DI
        $params = $this->matchParams($route, $pathParts);
        $callable = null;

        // callable could be DI
        if ($this->getContainer() !== null && is_array($route->getCallable()) && is_string($route->getCallable()[0])) {
            $obj = $this->getContainer()->get($route->getCallable()[0]);
            if ($obj !== null) {
                $method = $route->getCallable()[1];
                $callable = [$obj, $method];
            }
        }

        // if no Di object is found try and create an object
        if ($callable === null) {
            if (is_array($route->getCallable()) && is_string($route->getCallable()[0])) {
                $className = $route->getCallable()[0];
                $callable = [new $className(), $route->getCallable()[1]];
            } else {
                $callable = $route->getCallable();
            }
        }

        // call the callback function of the matched route with the
        return call_user_func_array($callable, $params);
    }

    /**
     * @param string $path
     * @param string[] $pathParts
     * @param string $method
     * @return Route|null
     */
    private function findMatch(string $path, array $pathParts, string $method): ?Route
    {
        $routes = $this->routeHandler->getRoutes();
        $match = $this->findDirectMatch($path, $method, $routes);
        if ($match === null) {
            $match = $this->findWildcardMatch($pathParts, $method, $routes);
        }

        return $match;
    }

    /**
     * @param string $path
     * @param string $method
     * @param Route[] $routes
     * @return Route|null
     */
    private function findDirectMatch(string $path, string $method, array $routes): ?Route
    {
        // find direct match
        foreach ($routes as $route) {
            if ($route->getPath() === $path && in_array($method, $route->getMethods(), true)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * @param string[] $pathParts
     * @param string $method
     * @param Route[] $routes
     * @return mixed|null
     */
    private function findWildcardMatch(array $pathParts, string $method, array $routes)
    {
        // find match with wildcard(s)
        foreach ($routes as $route) {
            if ($route->hasWildcard() && in_array($method, $route->getMethods(), true)) {
                $parts = $route->getRouteParts();
                $matches = true;
                $i = 0;
                foreach ($pathParts as $pathPart) {
                    $part = $parts[$i] ?? null;
                    $i++;

                    // if the part is not found it means that the request uri has more parts i.e. no match
                    if ($part === null) {
                        $matches = false;
                        break;
                    }

                    // if the part is a wildcard it does not have to match
                    if ($part->isWildcard()) {
                        continue;
                    }

                    // if the part is not a wildcard it does have to match
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
