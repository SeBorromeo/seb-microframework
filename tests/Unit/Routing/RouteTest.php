<?php

use PHPUnit\Framework\TestCase;
use SeBorromeo\SebMicroframework\Application;
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

    /* ---------- Dispatch ---------- */

    private function createGetRequest(Application $app): Request {
        return new Request($app, [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/path',
            'SERVER_PORT' => '8000',
            'PATH_INFO' => '/path',
            'HTTP_HOST' => 'localhost',
        ]);
    }

    public function testDispatch(): void {
        $route = new Route('/');
        $route->get(function($req, $res) {
            $res->send('Response Sent');
        });

        $appMock = $this->createMock(Application::class);
        
        $resMock = $this->createMock(Response::class);
        $resMock->expects($this->once())
            ->method('send')
            ->with($this->equalTo('Response Sent'));

        $req = $this->createGetRequest($appMock);

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

        
        $appMock = $this->createMock(Application::class);
        $resMock = $this->createMock(Response::class);
        $req = $this->createGetRequest($appMock);

        $doneCalled = false;
        $route->dispatch($req, $resMock, function() use (&$doneCalled) {
            $doneCalled = true;
        });

        $this->assertTrue($doneCalled);
        $this->assertFalse($middlewareCalled);
    }

    public function testDispatchErrorThrown() {
        $route = new Route('/');

        $route->get(function($req, $res) {
            throw new \Exception('Middleware failure');
        });

        $appMock = $this->createMock(Application::class);
        $resMock = $this->createMock(Response::class);
        $req = $this->createGetRequest($appMock);

        /** @var \Exception */
        $capturedError = null;

        $route->dispatch($req, $resMock, function($err = null) use (&$capturedError) {
            $capturedError = $err;
        });

        $this->assertInstanceOf(\Exception::class, $capturedError);
        $this->assertEquals('Middleware failure', $capturedError->getMessage());
    }

    public function testDispatchNextErr() {
        $route = new Route('/');

        $route->get(function($req, $res, $next) {
            $next(new \Exception('Next error'));
        });

        $middlewareCalled = false;

        $route->get(function($req, $res, $next) use (&$middlewareCalled) {
            $middlewareCalled = true;
            $next();
        });

        $appMock = $this->createMock(Application::class);
        $resMock = $this->createMock(Response::class);
        $req = $this->createGetRequest($appMock);

        /** @var \Exception */
        $capturedError = null;

        $route->dispatch($req, $resMock, function($err = null) use (&$capturedError) {
            $capturedError = $err;
        });

        $this->assertInstanceOf(\Exception::class, $capturedError);
        $this->assertEquals('Next error', $capturedError->getMessage());

        $this->assertFalse($middlewareCalled);
    }
}