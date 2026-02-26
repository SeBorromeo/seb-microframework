<?php

use PHPUnit\Framework\TestCase;
use SeBorromeo\SebMicroframework\Application;
use SeBorromeo\SebMicroframework\Http\HttpMethod;
use SeBorromeo\SebMicroframework\Http\Request;
use SeBorromeo\SebMicroframework\Http\Response;

class RequestTest extends TestCase {
    private Application $app;
    private Response $res;
    private array $defaultRequestMeta;

    protected function setUp(): void {
        parent::setUp();
        $this->app = new Application();
        $this->res = new Response($this->app);
        $this->defaultRequestMeta = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/path',
            'SERVER_PORT' => '8000',
            'PATH_INFO' => '/path',
            'HTTP_HOST' => 'localhost', // Required in HTTP/1.1 
        ];
    }

    public function testConstructor(): void {
        $req = new Request($this->app, $this->res, $this->defaultRequestMeta);

        $this->assertEquals(HttpMethod::GET, $req->method);
        $this->assertEquals('/path', $req->uri);
        $this->assertEquals(8000, $req->port());
        $this->assertEquals('/path', $req->path());
        $this->assertEquals('localhost', $req->host);
    }

    public function testMissingMetaData(): void {
        $this->expectException(\InvalidArgumentException::class);
        new Request($this->app, $this->res, []);
    }

    public function testIsContentType(): void {
        $this->defaultRequestMeta['CONTENT_TYPE'] = 'text/html; charset=utf-8';
        $req = new Request($this->app, $this->res, $this->defaultRequestMeta);

        $this->assertEquals('html', $req->is('html'));
        $this->assertEquals('text/html', $req->is('text/html'));
        $this->assertEquals('text/*', $req->is('text/*'));
        
        $this->defaultRequestMeta['CONTENT_TYPE'] = 'application/json';
        $req = new Request($this->app, $this->res, $this->defaultRequestMeta);
        
        $this->assertEquals('json', $req->is('json'));
        $this->assertEquals('application/json', $req->is('application/json'));
        $this->assertEquals('application/*', $req->is('application/*'));

        $this->assertEquals('json', $req->is(['json', 'html']));
        $this->assertFalse($req->is('html'));

        $this->expectException(\InvalidArgumentException::class);
        $req->is([1, 2]);
    }

    public function testMagicProperties(): void {
        $req = new Request($this->app, $this->res, $this->defaultRequestMeta);

        $req->userId = 1;
        $this->assertSame(1, $req->userId);
        
        $req->setAttribute('userId', 2);
        $this->assertSame(2, $req->getAttribute('userId'));

        $this->assertTrue(isset($req->userId));
        unset($req->userId);
        $this->assertFalse(isset($req->userId));

        $this->expectException(\LogicException::class);
        $req->params = [];
    }

    public function testQueryParsing(): void {
        $this->defaultRequestMeta['QUERY_STRING'] = 'q=solar&page=2&sort=asc';

        $req = new Request($this->app, $this->res, $this->defaultRequestMeta);

        $this->assertSame([
            'q' => 'solar',
            'page' => '2',
            'sort' => 'asc'
        ], $req->query());
    }

    public function testHeaderParsing(): void {
        $this->defaultRequestMeta['HTTP_X_CUSTOM_HEADER'] = 'custom-value';
        $this->defaultRequestMeta['CONTENT_TYPE'] = 'application/json';

        $req = new Request($this->app, $this->res, $this->defaultRequestMeta);

        $this->assertSame('custom-value', $req->header('X-CUSTOM-HEADER'));
        $this->assertSame('custom-value', $req->header('x-custom-header'));
        $this->assertSame('application/json', $req->header('Content-Type'));
        $this->assertNull($req->header('Non-Existent'));

        $this->assertEquals([
            'x-custom-header' => 'custom-value', 
            'content-type' => 'application/json',
            'host' => 'localhost'
        ], $req->headers());

        $this->assertTrue($req->hasHeader('X-CUSTOM-HEADER'));
        $this->assertTrue($req->hasHeader('x-custom-header'));
        $this->assertTrue($req->hasHeader('Content-Type'));
        $this->assertFalse($req->hasHeader('Non-Existent'));
    }
}
