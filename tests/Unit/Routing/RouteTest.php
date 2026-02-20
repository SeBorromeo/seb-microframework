<?php

use PHPUnit\Framework\TestCase;
use SeBorromeo\SebMicroframework\Http\HttpMethod;
use SeBorromeo\SebMicroframework\Http\Request;
use SeBorromeo\SebMicroframework\Http\Response;
use SeBorromeo\SebMicroframework\Routing\Route;

class RouteTest extends TestCase {
    public function testConstructor(): void {
        $route = new Route('/users/:id');

        $this->assertSame('/users/:id', $route->path);
    }

    public function testHandlesMethod(): void {
        $route = new Route('/users/:id');
        $route->get(function() {});

        $this->assertTrue($route->handlesMethod(HttpMethod::GET));
        $this->assertTrue($route->handlesMethod(HttpMethod::HEAD));
        $this->assertFalse($route->handlesMethod(HttpMethod::POST));
    }

    public function testMethods(): void {
        $route = new Route('/users/:id');
        $route->get(function() {});
        $route->post(function() {});

        $methods = $route->methods();
        $this->assertCount(2, $methods);
        $this->assertContains(HttpMethod::GET, $methods);
        $this->assertContains(HttpMethod::POST, $methods);
    }

    public function testAllMethod(): void {
        $route = new Route('/users/:id');
        $route->all(function() {});

        $this->assertTrue($route->handlesMethod(HttpMethod::GET));
        $this->assertTrue($route->handlesMethod(HttpMethod::POST));
        $this->assertTrue($route->handlesMethod(HttpMethod::PUT));
        $this->assertTrue($route->handlesMethod(HttpMethod::DELETE));
    }

    public function testInvalidHttpMethod(): void {
        $route = new Route('/');

        $this->expectException(\BadMethodCallException::class);
        $route->invalid(function () {});
    }

    public function testDispatch(): void {
        $route = new Route('/');
        $route->get(function($req, $res) {
            $res->send('Response Sent');
        });

        $req = new Request(['REQUEST_METHOD' => 'GET']);
        $resMock = $this->createMock(Response::class);

        $resMock->expects($this->once())
            ->method('send')
            ->with($this->equalTo('Response Sent'));

        $route->dispatch($req, $resMock, function() {});
    }

    public function testDispatchExitRoute(): void {
        $route = new Route('/');
        $route->get(function($req, $res, $next) {
            $next('route');
        });

        $middlewareCalled = false;
        $route->get(function($req, $res, $next) use (&$middlewareCalled) {
            $middlewareCalled = true;
            $next();
        });

        $req = new Request(['REQUEST_METHOD' => 'GET']);
        $resMock = $this->createMock(Response::class);

        $doneCalled = false;
        $route->dispatch($req, $resMock, function() use (&$doneCalled) {
            $doneCalled = true;
        });

        $this->assertTrue($doneCalled);
        $this->assertFalse($middlewareCalled);
    }

    public function testDispatchErrorThrown() {

    }

    public function testDispatchNextErr() {

    }


}