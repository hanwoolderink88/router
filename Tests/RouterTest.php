<?php
declare(strict_types=1);

namespace Hanwoolderink88\Router\Tests;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use HanWoolderink88\Container\Container;
use Hanwoolderink88\Router\Route;
use Hanwoolderink88\Router\Router;
use Hanwoolderink88\Router\RouterMatchException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RouterTest extends TestCase
{
    public function testBasicRouting(): void
    {
        $router = new Router();

        $route = new Route(
            '/', 'home', ['GET'], function () {
            return new Response(200, [], 'jhie');
        }
        );
        $router->getRouteHandler()->addRoute($route);

        $request = new ServerRequest('GET', '/');
        $response = $router->handle($request);

        $responseBody = $response->getBody()->__tostring();

        $this->assertEquals('jhie', $responseBody, 'not matching response bodies');
    }

    public function testWildcardRouting(): void
    {
        $router = new Router();

        $route = new Route('/{id}/{hi}', 'home', ['GET'], [$this, 'routerResponse']);
        $router->getRouteHandler()->addRoute($route);
        $router->setResponse404(new Response(200, [], '404'));

        $request = new ServerRequest('GET', '/test/han');
        $response = $router->handle($request);
        $responseBody = $response->getBody()->__tostring();

        $expect = json_encode(['id' => 'test', 'hi' => 'han']);
        $this->assertEquals($expect, $responseBody, 'not matching response bodies');
    }

    public function testDIRoute(): void
    {
        $container = new Container();
        $container->addServiceReference(TestDi::class);
        $container->buildIndex();

        $router = new Router();
        $router->setContainer($container);
        $router->setResponse404(new Response(200, [], '404'));

        $route = new Route('/', 'home', ['GET'], [$this, 'routerResponseDi']);
        $router->getRouteHandler()->addRoute($route);

        $request = new ServerRequest('GET', '/');
        $response = $router->handle($request);
        $responseBody = $response->getBody()->__tostring();

        $expect = json_encode(['foo' => 'bar']);
        $this->assertEquals($expect, $responseBody, 'not matching response bodies');
    }

    public function testDiController(): void
    {
        $container = new Container();
        $container->addServiceReference(TestDi::class);
        $container->addServiceReference(Controller2::class);
        $container->buildIndex();

        $router = new Router();
        $router->setContainer($container);
        $router->setResponse404(new Response(200, [], '404'));

        $route = new Route('/{hi}', 'home', ['GET'], [Controller2::class, 'homePage']);
        $router->getRouteHandler()->addRoute($route);

        $request = new ServerRequest('GET', '/jhie');
        $response = $router->handle($request);
        $responseBody = $response->getBody()->__tostring();

        $expect = json_encode(['hi' => 'jhie', 'foo' => 'bar']);
        $this->assertEquals($expect, $responseBody, 'not matching response bodies');
    }

    public function testIncorrectRouteConfig(): void
    {
        $container = new Container();
        $router = new Router();
        $router->setContainer($container);

        $route = new Route('/{hi}', 'home', ['GET'], [FooBar2::class, 'homePage']);
        $router->getRouteHandler()->addRoute($route);

        $this->expectException(RouterMatchException::class);
        $request = new ServerRequest('GET', '/foobar');
        $router->handle($request);
    }

    public function test404Routing(): void
    {
        // assert 1: match response of 404
        $router = new Router();
        $router->setResponse404(new Response(404, [], 'HTTP 404: not found'));
        $request = new ServerRequest('GET', '/');
        $match = $router->handle($request);
        $this->assertEquals('HTTP 404: not found', $match->getBody()->__toString(), '404 body does not match');

        // assert 2: remove route
        $router->getRouteHandler()->addRoute(new Route('/', 'testRoute', ['GET'], [Controller2::class, 'homePage']));
        $router->getRouteHandler()->removeRouteByName('testRoute');
        $match2 = $router->handle($request);
        $this->assertEquals('HTTP 404: not found', $match2->getBody()->__toString(), '404 body does not match');

        // assert 3: exception when no 404 response is set
        $router = new Router();
        $this->expectException(RouterMatchException::class);
        $router->handle($request);
    }

    /**
     * @param string $hi
     * @param string $id
     * @return ResponseInterface
     */
    public function routerResponse($hi, string $id): ResponseInterface
    {
        return new Response(200, [], json_encode(['id' => $id, 'hi' => $hi]));
    }

    /**
     * @param TestDi $jhie
     * @return ResponseInterface
     */
    public function routerResponseDi(TestDi $jhie): ResponseInterface
    {
        return new Response(200, [], json_encode(['foo' => $jhie->foo()]));
    }
}

class Controller2
{
    private TestDi $testDi;

    public function __construct(TestDi $testDi)
    {
        $this->testDi = $testDi;
    }

    public function homePage(string $hi)
    {
        return new Response(200, [], json_encode(['hi' => $hi, 'foo' => $this->testDi->foo()]));
    }
}

class FooBar2
{
    public function homePage(TestDi $testDi, string $hi)
    {
        return new Response(200, [], json_encode(['hi' => $hi, 'foo' => $testDi->foo()]));
    }
}

class TestDi
{
    public function foo(): string
    {
        return 'bar';
    }
}