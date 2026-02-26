<?php

use PHPUnit\Framework\TestCase;
use SeBorromeo\SebMicroframework\Application;
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
            'HTTP_HOST' => 'localhost',
            'SERVER_PORT' => '8000',
            'PATH_INFO' => '/path'
        ];
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
