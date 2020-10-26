<?php

namespace Hanwoolderink88\Router;

class RouteHandler
{
    /**
     * @var Route[]
     */
    protected array $routes = [];

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param Route[] $routes
     * @return self
     * @throws RouterAddRouteException
     */
    public function setRoutes(array $routes): self
    {
        $this->routes = [];

        $this->addRoutes($routes);

        return $this;
    }

    /**
     * @param Route[] $routes
     * @return self
     * @throws RouterAddRouteException
     */
    public function addRoutes(array $routes): self
    {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }

        return $this;
    }

    /**
     * @param Route $route
     * @return self
     * @throws RouterAddRouteException
     */
    public function addRoute(Route $route): self
    {
        $routes = $this->getRoutes();
        foreach ($routes as $registeredRoute) {
            $sharedMethods = array_intersect($route->getMethods(), $registeredRoute->getMethods());
            if ($registeredRoute->getPath() === $route->getPath() && count($sharedMethods) > 0) {
                throw new RouterAddRouteException("route with path \"/{$route->getPath()}\" already exists");
            }

            // overwrite/remove routes with the same name
            if ($registeredRoute->getName() === $route->getName()) {
                $this->removeRoute($registeredRoute);
            }
        }

        $this->routes[] = $route;

        return $this;
    }

    /**
     * @param Route $route
     * @return self
     */
    public function removeRoute(Route $route): self
    {
        $max = count($this->routes);
        for ($i = 0; $i < $max; $i++) {
            $foundRoute = $this->routes[$i];
            if ($foundRoute->getName() === $route->getName()) {
                unset($this->routes[$i]);
                reset($this->routes);

                // as names are unique we can break;
                break;
            }
        }

        return $this;
    }

    /**
     * @param string $name
     * @return self
     */
    public function removeRouteByName(string $name): self
    {
        $max = count($this->routes);
        for ($i = 0; $i < $max; $i++) {
            $foundRoute = $this->routes[$i];
            if ($foundRoute->getName() === $name) {
                unset($this->routes[$i]);
                reset($this->routes);

                // as names are unique we can break;
                break;
            }
        }

        return $this;
    }
}
