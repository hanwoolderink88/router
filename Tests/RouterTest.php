<?php

namespace Tests;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use HanWoolderink88\Container\Container;
use Hanwoolderink88\Router\Route;
use Hanwoolderink88\Router\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RouterTest extends TestCase
{
    public function testBasicRouting(): void
    {
        $router = new Router();

        $route = new Route(
            '/',
            'home',
            ['GET'],
            function () {
                return new Response(200, [], 'jhie');
            }
        );
        $router->addRoute($route);

        $request = new ServerRequest('GET', '/');
        $response = $router->handle($request);

        $responseBody = $response->getBody()->__tostring();

        $this->assertEquals('jhie', $responseBody, 'not matching response bodies');
    }

    public function testWildcardRouting(): void
    {
        $router = new Router();

        $route = new Route('/{id}/{hi}', 'home', ['GET'], [$this, 'routerResponse']);
        $router->addRoute($route);

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

        $route = new Route('/', 'home', ['GET'], [$this, 'routerResponseDi']);
        $router->addRoute($route);

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
        $container->addServiceReference(Controller::class);
        $container->buildIndex();

        $router = new Router();
        $router->setContainer($container);

        $route = new Route('/{hi}', 'home', ['GET'], [Controller::class, 'homePage']);
        $router->addRoute($route);

        $request = new ServerRequest('GET', '/jhie');
        $response = $router->handle($request);
        $responseBody = $response->getBody()->__tostring();

        $expect = json_encode(['hi' => 'jhie', 'foo' => 'bar']);
        $this->assertEquals($expect, $responseBody, 'not matching response bodies');
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

class Controller
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

class TestDi
{
    public function foo(): string
    {
        return 'bar';
    }
}