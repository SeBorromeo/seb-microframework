<?php

use PHPUnit\Framework\TestCase;
use Sebastian\MicroFramework\Http\Response;
use Sebastian\MicroFramework\Application;
use Sebastian\MicroFramework\Exceptions\Http\InvalidRendererException;
use Sebastian\MicroFramework\View\AbstractRenderer;

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
        $this->assertContains('Accept', $res->getHeaderNames());
    }

    public function testAppendHeaderStrToStr(): void {
        $res = new Response($this->app);
        $res->set('Link', '<http://localhost/>');
        $res->append('Link', '<http://localhost:3000/>');

        $this->assertEquals(
            ['Link' => ['<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testAppendHeaderListToStr(): void {
        $res = new Response($this->app);
        $res->set('Link', '<http://localhost:8000/>');
        $res->append('Link', ['<http://localhost/>', '<http://localhost:3000/>']);

        $this->assertEquals(
            ['Link' => ['<http://localhost:8000/>', '<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testAppendHeaderListToList(): void {
        $res = new Response($this->app);
        $res->set('Link', ['<http://localhost:8000/>']);
        $res->append('Link', ['<http://localhost/>', '<http://localhost:3000/>']);

        $this->assertEquals(
            ['Link' => ['<http://localhost:8000/>', '<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testAppendHeaderStrToList(): void {
        $res = new Response($this->app);
        $res->set('Link', ['<http://localhost:8000/>', '<http://localhost/>']);
        $res->append('Link', '<http://localhost:3000/>');

        $this->assertEquals(
            ['Link' => ['<http://localhost:8000/>', '<http://localhost/>', '<http://localhost:3000/>']],
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

    /* ---------- View ---------- */

    public function testAddLocalViewVariable(): void {
        $res = new Response($this->app);
        $res->addLocalVar('name', 'Sebastian');

        $this->assertEquals(['name' => 'Sebastian'], $res->locals());
        
        $res->addLocalVar('name', 'Seb');

        $this->assertEquals(['name' => 'Seb'], $res->locals());
    }

    public function testLocalsAddedWithRender(): void {
        $this->app->set('view engine', 'php');
        $res = $this->getMockBuilder(Response::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['createRenderer'])
            ->getMock();

        $mockRenderer = $this->createMock(AbstractRenderer::class);

        $res->method('createRenderer')->willReturn($mockRenderer);

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
        $this->expectException(InvalidRendererException::class);
        $this->expectExceptionMessage('engine must not be null');
        $res->render('index.php', ['name' => 'Sebastian']);
    }

    public function testRenderThrowsWhenInvalidRenderer(): void {
        $this->app->set('view engine', 'txt');
        $res = new Response($this->app);
        $this->expectException(InvalidRendererException::class);
        $this->expectExceptionMessage('is not supported');
        $res->render('index.php', ['name' => 'Sebastian']);
    }

    
}
