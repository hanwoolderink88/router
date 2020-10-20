<?php

namespace Tests;

use GuzzleHttp\Psr7\Response;
use Hanwoolderink88\Router\Route;
use Hanwoolderink88\Router\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RouterTest extends TestCase
{
    public function testBasicRouting(): void
    {
        $router = new Router();

        $route = new Route('/', 'home', ['GET'], function () {
            return new Response(200, [], 'jhie');
        });
        $router->addRoute($route);

        $path = '/';
        $response = $router->match($path, 'GET');

        $responseBody = $response->getBody()->__tostring();

        $this->assertEquals('jhie', $responseBody, 'not matching response bodies');
    }

    public function testWildcardRouting(): void
    {
        $router = new Router();

        $route = new Route('/{id}/{hi}', 'home', ['GET'], [$this, 'routerResponse']);
        $router->addRoute($route);

        $path = '/test/han';
        $response = $router->match($path, 'GET');
        $responseBody = $response->getBody()->__tostring();

        $expect = json_encode(['foo' => 'bar', 'id' => 'test', 'hi' => 'han']);
        $this->assertEquals($expect, $responseBody, 'not matching response bodies');
    }

    /**
     * @param string $hi
     * @param string $id
     * @return ResponseInterface
     */
    public function routerResponse($hi, string $id): ResponseInterface
    {
        return new Response(200, [], json_encode(['foo' => 'bar', 'id' => $id, 'hi' => $hi]));
    }
}
