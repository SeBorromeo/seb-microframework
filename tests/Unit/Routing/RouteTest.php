<?php

use PHPUnit\Framework\TestCase;
use Sebastian\MicroFramework\Http\Request;
use Sebastian\MicroFramework\Http\Response;
use Sebastian\MicroFramework\Routing\Route;

class RouteTest extends TestCase {
    public function testConstructor(): void {
        $route = new Route('/users/:id');

        $this->assertSame('/users/:id', $route->path);
    }

    public function testHandlesMethod(): void {
        $route = new Route('/users/:id');
        $route->get(function() {});

        $this->assertTrue($route->handlesMethod('GET'));
        $this->assertTrue($route->handlesMethod('HEAD'));
        $this->assertFalse($route->handlesMethod('POST'));
    }

    public function testMethods(): void {
        $route = new Route('/users/:id');
        $route->get(function() {});
        $route->post(function() {});

        $methods = $route->methods();
        $this->assertCount(2, $methods);
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
    }

    public function testAllMethod(): void {
        $route = new Route('/users/:id');
        $route->all(function() {});

        $this->assertTrue($route->handlesMethod('GET'));
        $this->assertTrue($route->handlesMethod('POST'));
        $this->assertTrue($route->handlesMethod('PUT'));
        $this->assertTrue($route->handlesMethod('DELETE'));
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