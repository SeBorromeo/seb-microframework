<?php

use PHPUnit\Framework\TestCase;
use SeBorromeo\SebMicroframework\Http\Request;
use SeBorromeo\SebMicroframework\Http\Response;
use SeBorromeo\SebMicroframework\Routing\Layer;
use SeBorromeo\PathToRegex\Regex;

class LayerTest extends TestCase {
    /* ---------- Constructor ---------- */

    public function testConstructorWithClosure(): void {
        $handle = function() {};
        $layer = new Layer('/users/:id', [], $handle);

        $this->assertSame($handle, $layer->handle);
        $this->assertSame('<anonymous>', $layer->name);
    }

    public function testConstructorWithNamedFunction(): void {
        function handler() {}
        $layer = new Layer('/users/:id', [], 'handler');

        $this->assertSame('handler', $layer->handle);
        $this->assertSame('handler', $layer->name);
    }

    public function testConstructorWithInvalidHandler(): void {
        $this->expectException(InvalidArgumentException::class);
        new Layer('/users/:id', [], 'not_a_function');
    }

    /* ---------- Match ---------- */

    public function testMatchSimplePath(): void {
        $layer = new Layer('/users/list');
        $matchResult = $layer->match('/users/list');

        $this->assertNotNull($matchResult);
        $this->assertSame([], $matchResult->params);
    }

    public function testMatchWithParameter(): void {
        $layer = new Layer('/posts/:year/:month');
        $matchResult = $layer->match('/posts/2023/06');
        
        $this->assertNotNull($matchResult);
        $this->assertSame('/posts/2023/06', $matchResult->path);
        $this->assertSame(['year' => ['2023'], 'month' => ['06']], $matchResult->params);
    }

    public function testMatchWithWildcard(): void {
        $layer = new Layer('/files/*filepath');
        $matchResult = $layer->match('/files/images/photo.jpg');

        $this->assertNotNull($matchResult);
        $this->assertSame('/files/images/photo.jpg', $matchResult->path);
        $this->assertSame(['filepath' => ['images', 'photo.jpg']], $matchResult->params);
    }

    public function testMatchWithRegex(): void {
        $layer = new Layer(new Regex('/^\/users\/(\d+)$/'));

        $matchResult = $layer->match('/users/');
        $this->assertNull($matchResult);
        
        $matchResult = $layer->match('/users/123/profile');
        $this->assertNull($matchResult);

        $matchResult = $layer->match('/users/123');
        $this->assertNotNull($matchResult);
        $this->assertSame('/users/123', $matchResult->path);
        $this->assertSame(['0' => '123'], $matchResult->params);
    }

    public function testMatchWithRegexNamedGroup(): void {
        $layer = new Layer(new Regex('/^\/users\/(?<id>\d+)$/'));

        $matchResult = $layer->match('/users/123');
        $this->assertNotNull($matchResult);
        $this->assertSame('/users/123', $matchResult->path);
        $this->assertSame(['id' => '123'], $matchResult->params);
    }

    public function testMatchWithArrayOfPaths(): void {
        $layer = new Layer(['/users/list', '/users/:id']);

        $matchResult = $layer->match('/users/list');
        $this->assertNotNull($matchResult);
        $this->assertSame('/users/list', $matchResult->path);
        $this->assertSame([], $matchResult->params);

        $matchResult = $layer->match('/users/123');
        $this->assertNotNull($matchResult);
        $this->assertSame('/users/123', $matchResult->path);
        $this->assertSame(['id' => ['123']], $matchResult->params);
    }

    public function testIncorrectMatch(): void {
        $layer = new Layer('/users/:id');

        $this->assertNull($layer->match('/users/'));
        $this->assertNull($layer->match('/users/123/profile'));
    }

    public function testMatchSlash(): void {
        $layer = new Layer('/', ['end' => false]);

        $matchResult = $layer->match('/');
        $this->assertNotNull($matchResult);
        $this->assertSame('', $matchResult->path);
        $this->assertSame([], $matchResult->params);
    }

    /* ---------- Handle Error ---------- */

    public function testHandleErrorWithNonErrorHandler(): void {
        $handlerReached = false;
        $layer = new Layer('/', [], function($req, $res, $next) use (&$handlerReached) {
            $handlerReached = true;
        });

        $reqMock = $this->createMock(Request::class);
        $resMock = $this->createMock(Response::class);

        $nextReached = false;
        $errorValue = '';
        $layer->handleError('error', $reqMock, $resMock, function($err) use (&$nextReached, &$errorValue) {
            $nextReached = true;
            $errorValue = $err;
        });

        $this->assertTrue($nextReached);
        $this->assertFalse($handlerReached);
        $this->assertSame('error', $errorValue);
    }

    public function testHandleErrorWithErrorHandler(): void {
        $handlerReached = false;
        $errorValue = '';
        $layer = new Layer('/', [], function($err, $req, $res, $next) use (&$handlerReached, &$errorValue) {
            $handlerReached = true;
            $errorValue = $err;
        });

        $reqMock = $this->createMock(Request::class);
        $resMock = $this->createMock(Response::class);

        $nextReached = false;
        $layer->handleError('error', $reqMock, $resMock, function($err) use (&$nextReached) {
            $nextReached = true;
        });

        $this->assertFalse($nextReached);
        $this->assertTrue($handlerReached);
        $this->assertSame('error', $errorValue);
    }

    public function testHandleErrorWithErrorThrown(): void {
        $layer = new Layer('/', [], function($err, $req, $res, $next) {
            throw new \Exception('thrown error');
        });

        $reqMock = $this->createMock(Request::class);
        $resMock = $this->createMock(Response::class);

        /** @var \Exception */
        $errorValue = null;
        $layer->handleError('error', $reqMock, $resMock, function($err) use (&$errorValue) {
            $errorValue = $err;
        });

        $this->assertInstanceOf(\Exception::class, $errorValue);
        $this->assertSame('thrown error', $errorValue->getMessage());
    }

    /* ---------- Handle Request ---------- */

    public function testHandleRequestWithNonErrorHandler(): void {
        $handlerReached = false;
        $layer = new Layer('/', [], function($req, $res, $next) use (&$handlerReached) {
            $handlerReached = true;
        });

        $reqMock = $this->createMock(Request::class);
        $resMock = $this->createMock(Response::class);

        $nextReached = false;
        $layer->handleRequest($reqMock, $resMock, function() use (&$nextReached) {
            $nextReached = true;
        });

        $this->assertFalse($nextReached);
        $this->assertTrue($handlerReached);
    }

    public function testHandleRequestWithErrorHandler(): void {
        $handlerReached = false;
        $layer = new Layer('/', [], function($err, $req, $res, $next) use (&$handlerReached) {
            $handlerReached = true;
        });

        $reqMock = $this->createMock(Request::class);
        $resMock = $this->createMock(Response::class);

        $nextReached = false;
        $layer->handleRequest($reqMock, $resMock, function() use (&$nextReached) {
            $nextReached = true;
        });

        $this->assertTrue($nextReached);
        $this->assertFalse($handlerReached);
    }

    public function testHandleRequestWithErrorThrown(): void {
        $layer = new Layer('/', [], function($req, $res, $next) {
            throw new \Exception('thrown error');
        });

        $reqMock = $this->createMock(Request::class);
        $resMock = $this->createMock(Response::class);

        /** @var \Exception */
        $errorValue = null;
        $layer->handleRequest($reqMock, $resMock, function($err) use (&$errorValue) {
            $errorValue = $err;
        });

        $this->assertInstanceOf(\Exception::class, $errorValue);
        $this->assertSame('thrown error', $errorValue->getMessage());
    }
}