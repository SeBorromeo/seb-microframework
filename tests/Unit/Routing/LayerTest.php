<?php

use PHPUnit\Framework\TestCase;
use Sebastian\MicroFramework\Http\Request;
use Sebastian\MicroFramework\Http\Response;
use Sebastian\MicroFramework\Routing\Layer;
use Sebastian\MicroFramework\Routing\Lib\Regex;

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

        $this->assertTrue($layer->match('/users/list'));
        $this->assertSame('/users/list', $layer->path);
        $this->assertSame([], $layer->params);
    }

    public function testMatchWithParameter(): void {
        $layer = new Layer('/posts/:year/:month');

        $this->assertTrue($layer->match('/posts/2023/06'));
        $this->assertSame('/posts/2023/06', $layer->path);
        $this->assertSame(['year' => ['2023'], 'month' => ['06']], $layer->params);
    }

    public function testMatchWithWildcard(): void {
        $layer = new Layer('/files/*filepath');

        $this->assertTrue($layer->match('/files/images/photo.jpg'));
        $this->assertSame('/files/images/photo.jpg', $layer->path);
        $this->assertSame(['filepath' => ['images', 'photo.jpg']], $layer->params);
    }

    public function testMatchWithRegex(): void {
        $layer = new Layer(new Regex('/^\/users\/(\d+)$/'));

        $this->assertFalse($layer->match('/users/'));
        $this->assertFalse($layer->match('/users/123/profile'));
        $this->assertTrue($layer->match('/users/123'));
        $this->assertSame('/users/123', $layer->path);
        $this->assertSame(['0' => '123'], $layer->params);
    }

    public function testMatchWithRegexNamedGroup(): void {
        $layer = new Layer(new Regex('/^\/users\/(?<id>\d+)$/'));

        $this->assertTrue($layer->match('/users/123'));
        $this->assertSame('/users/123', $layer->path);
        $this->assertSame(['id' => '123'], $layer->params);
    }

    public function testMatchWithArrayOfPaths(): void {
        $layer = new Layer(['/users/list', '/users/:id']);

        $this->assertTrue($layer->match('/users/list'));
        $this->assertSame('/users/list', $layer->path);
        $this->assertSame([], $layer->params);

        $this->assertTrue($layer->match('/users/123'));
        $this->assertSame('/users/123', $layer->path);
        $this->assertSame(['id' => ['123']], $layer->params);
    }

    public function testIncorrectMatch(): void {
        $layer = new Layer('/users/:id');

        $this->assertFalse($layer->match('/users/'));
        $this->assertFalse($layer->match('/users/123/profile'));
    }

    public function testMatchSlash(): void {
        $layer = new Layer('/', ['end' => false]);

        $this->assertTrue($layer->match('/'));
        $this->assertSame('', $layer->path);
        $this->assertSame([], $layer->params);
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
}