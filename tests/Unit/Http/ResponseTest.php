<?php

use PHPUnit\Framework\TestCase;
use Sebastian\MicroFramework\Http\Response;
use Sebastian\MicroFramework\Application;

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

    
}
