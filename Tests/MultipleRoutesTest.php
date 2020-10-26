<?php
declare(strict_types=1);

namespace Hanwoolderink88\Router\Tests;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Hanwoolderink88\Router\Route;
use Hanwoolderink88\Router\Router;
use Hanwoolderink88\Router\RouterAddRouteException;
use Hanwoolderink88\Router\RouterMatchException;
use PHPStan\Testing\TestCase;

class MultipleRoutesTest extends TestCase
{
    public function testRestfullRoutes(): void
    {
        $msg = 'Response for %s does not match';
        $router = $this->createRouterWithRestRoutes();

        // check if the total count matches and for each route if the response matches
        $this->assertEquals(6, count($router->getRouteHandler()->getRoutes()), 'route count does not match');
        $this->assertEquals('homepage', $this->cr($router, 'GET', '/'), sprintf($msg, 'homePage'));
        $this->assertEquals('usersList', $this->cr($router, 'GET', '/users'), sprintf($msg, 'userList'));
        $this->assertEquals('usersAdd', $this->cr($router, 'POST', '/users'), sprintf($msg, 'usersAdd'));
        $this->assertEquals('usersView', $this->cr($router, 'GET', '/users/1'), sprintf($msg, 'usersView'));
        $this->assertEquals('usersEdit', $this->cr($router, 'PUT', '/users/1'), sprintf($msg, 'usersEdit'));
        $this->assertEquals('usersEdit', $this->cr($router, 'PATCH', '/users/1'), sprintf($msg, 'usersEdit'));
        $this->assertEquals('usersDelete', $this->cr($router, 'DELETE', '/users/1'), sprintf($msg, 'usersDelete'));
    }

    public function testRouteNotFoundLong():void
    {
        $this->expectException(RouterMatchException::class);
        $router = $this->createRouterWithRestRoutes();
        $request = new ServerRequest('GET', '/users/1/delete/jhie/');
        $router->handle($request);
    }

    public function testRouteNotFoundMismatchNonWildcard():void
    {
        $this->expectException(RouterMatchException::class);
        $router = $this->createRouterWithRestRoutes();
        $request = new ServerRequest('GET', '/foobar');
        $router->handle($request);
    }

    public function testOverwriteByName(): void
    {
        $routes = [
            new Route('/', 'testRoute', ['GET'], [Controller::class, 'homePage']),
            new Route('/users/{id}', 'testRoute', ['GET'], [Controller::class, 'usersList']),
        ];

        $router = new Router();
        $router->getRouteHandler()->setRoutes($routes);

        $this->expectException(RouterMatchException::class);
        $request = new ServerRequest('GET', '/');
        $router->handle($request);
    }

    public function testOverwriteBySignature(): void
    {
        $routes = [
            new Route('/', 'testRoute', ['GET'], [Controller::class, 'homePage']),
            new Route('/', 'test2Route', ['GET'], [Controller::class, 'usersList']),
        ];

        $router = new Router();
        $this->expectException(RouterAddRouteException::class);
        $router->getRouteHandler()->setRoutes($routes);
    }

    private function createRouterWithRestRoutes()
    {
        $routes = [
            new Route('/', 'homePage', ['GET'], [Controller::class, 'homePage']),
            new Route('/users', 'usersList', ['GET'], [Controller::class, 'usersList']),
            new Route('/users', 'usersAdd', ['POST'], [Controller::class, 'usersAdd']),
            new Route('/users/{id}', 'usersView', ['GET'], [Controller::class, 'usersView']),
            new Route('/users/{id}', 'usersEdit', ['PUT', 'PATCH'], [Controller::class, 'usersEdit']),
            new Route('/users/{id}', 'usersDelete', ['DELETE'], [Controller::class, 'usersDelete']),
        ];

        $router = new Router();
        $router->getRouteHandler()->setRoutes($routes);

        return $router;
    }

    //cr = create response (body as a string)
    private function cr(Router $router, $method, $path)
    {
        $request = new ServerRequest($method, $path);

        return $router->handle($request)->getBody()->__toString();
    }
}

class Controller
{
    public function homePage()
    {
        return new Response(200, [], "homepage");
    }

    public function usersList()
    {
        return new Response(200, [], "usersList");
    }

    public function usersAdd()
    {
        return new Response(200, [], "usersAdd");
    }

    public function usersView()
    {
        return new Response(200, [], "usersView");
    }

    public function usersEdit()
    {
        return new Response(200, [], "usersEdit");
    }

    public function usersDelete()
    {
        return new Response(200, [], "usersDelete");
    }
}
