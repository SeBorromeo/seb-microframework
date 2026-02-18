<?php

use PHPUnit\Framework\TestCase;
use SeBorromeo\SebMicroframework\Http\Response;
use SeBorromeo\SebMicroframework\Application;
use SeBorromeo\SebMicroframework\Exceptions\Application\InvalidEngineException;
use SeBorromeo\SebMicroframework\View\Engine\PhpEngine;

class ResponseTest extends TestCase {
    private Application $app;

    protected function setUp(): void {
        parent::setUp();
        $this->app = new Application();
    }

    /* ---------- Headers ---------- */

    public function testHeadersStartEmpty(): void {
        $res = new Response($this->app);

        $this->assertEquals([], $res->getHeaders());
        $this->assertEquals([], $res->getHeaderNames());
        $this->assertNull($res->getHeader('Accept'));
        $this->assertFalse($res->hasHeader('Accept'));
    }

    public function testSetHeader(): void {
        $res = new Response($this->app);
        $res->set('Accept', ['text/html', 'application/json', 'image/png']);

        $this->assertEquals(['text/html', 'application/json', 'image/png'], $res->getHeader('Accept'));
        $this->assertTrue($res->hasHeader('Accept'));
        $this->assertContains('accept', $res->getHeaderNames());
    }

    public function testAppendHeaderStrToStr(): void {
        $res = new Response($this->app);
        $res->set('Link', '<http://localhost/>');
        $res->append('Link', '<http://localhost:3000/>');

        $this->assertEquals(
            ['link' => ['<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testAppendHeaderListToStr(): void {
        $res = new Response($this->app);
        $res->set('Link', '<http://localhost:8000/>');
        $res->append('Link', ['<http://localhost/>', '<http://localhost:3000/>']);

        $this->assertEquals(
            ['link' => ['<http://localhost:8000/>', '<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testAppendHeaderListToList(): void {
        $res = new Response($this->app);
        $res->set('Link', ['<http://localhost:8000/>']);
        $res->append('Link', ['<http://localhost/>', '<http://localhost:3000/>']);

        $this->assertEquals(
            ['link' => ['<http://localhost:8000/>', '<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testAppendHeaderStrToList(): void {
        $res = new Response($this->app);
        $res->set('Link', ['<http://localhost:8000/>', '<http://localhost/>']);
        $res->append('Link', '<http://localhost:3000/>');

        $this->assertEquals(
            ['link' => ['<http://localhost:8000/>', '<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testRemoveHeader(): void {
        $res = new Response($this->app);
        $res->set('Accept', ['text/html']);
        $res->removeHeader('Accept');

        $this->assertFalse($res->hasHeader('Accept'));
        $this->assertNotContains('Accept', $res->getHeaderNames());
    }

    /* ---------- Status ---------- */

    public function testDefaultStatus(): void {
        $res = new Response($this->app);
        $this->assertSame(200, $res->statusCode());
        $this->assertSame('OK', $res->statusMessage());
    }
        
    public function testSetStatusAndDefaultMessage(): void {
        $res = new Response($this->app);
        $res->status(404);

        $this->assertSame(404, $res->statusCode());
        $this->assertSame('Not Found', $res->statusMessage());
    }
    
    /* ---------- Cookies ---------- */


    /* ---------- View ---------- */

    public function testAddLocalViewVariable(): void {
        $res = new Response($this->app);
        $res->addLocalVar('name', 'Sebastian');

        $this->assertEquals(['name' => 'Sebastian'], $res->locals());
        
        $res->addLocalVar('name', 'Seb');

        $this->assertEquals(['name' => 'Seb'], $res->locals());
    }

    public function testLocalsAddedWithRender(): void {
        $mockApp = $this->getMockBuilder(Application::class)
            ->onlyMethods(['getEngine'])
            ->getMock();

        $res = $this->getMockBuilder(Response::class)
            ->setConstructorArgs([$mockApp])
            ->onlyMethods(['resolvePath'])
            ->getMock();
        $res->method('resolvePath')->willReturn('/fake/path/index.php');

        $mockPhpEngine = $this->createMock(PhpEngine::class);
        $mockPhpEngine->method('render')->willReturn('');

        $mockApp->method('getEngine')->willReturn($mockPhpEngine);

        $res->render('index.php', ['name' => 'Sebastian']);

        $this->assertEquals(['name' => 'Sebastian'], $res->locals());
    }

    public function testRenderThrowsWhenViewPathNotConfigured(): void {
        $this->app->set('view path', null);
        $res = new Response($this->app);
        $this->expectException(\LogicException::class);
        $res->render('index.php', ['name' => 'Sebastian']);
    }

    public function testRenderThrowsWhenRendererNull(): void {
        $res = new Response($this->app);
        $this->expectException(InvalidEngineException::class);
        $this->expectExceptionMessage('engine must not be null');
        $res->render('index.php', ['name' => 'Sebastian']);
    }

    public function testRenderThrowsWhenInvalidRenderer(): void {
        $this->app->set('view engine', 'txt');
        $res = new Response($this->app);
        $this->expectException(InvalidEngineException::class);
        $this->expectExceptionMessage('is not supported');
        $res->render('index.php', ['name' => 'Sebastian']);
    }

    /* ---------- Send ---------- */

    public function testHeadersSent(): void {

    }

    public function testRequestEnded(): void {

    }
}
