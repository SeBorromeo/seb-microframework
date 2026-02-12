<?php

use PHPUnit\Framework\TestCase;
use Sebastian\MicroFramework\Http\Request;

class RequestTest extends TestCase {
    public function testMagicProperties(): void {
        $req = new Request();

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

    public function testContentTypeParsing(): void {
        $serverParams = [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset= 8',
        ];

        $req = new Request($serverParams);

        $this->assertSame('application/x-www-form-urlencoded', $req->contentType());
    }

    public function testQueryParsing(): void {
        $serverParams = [
            'QUERY_STRING' => 'q=solar&page=2&sort=asc',
        ];

        $req = new Request($serverParams);

        $this->assertSame([
            'q' => 'solar',
            'page' => '2',
            'sort' => 'asc'
        ], $req->query());
    }

    public function testHeaderParsing(): void {
        $serverParams = [
            'HTTP_X_CUSTOM_HEADER' => 'custom-value',
            'CONTENT_TYPE' => 'application/json',
        ];

        $req = new Request($serverParams);

        $this->assertSame('custom-value', $req->header('X-CUSTOM-HEADER'));
        $this->assertSame('custom-value', $req->header('x-custom-header'));
        $this->assertSame('application/json', $req->header('Content-Type'));
        $this->assertNull($req->header('Non-Existent'));

        $this->assertEquals(['x-custom-header' => 'custom-value', 'content-type' => 'application/json'], $req->headers());

        $this->assertTrue($req->hasHeader('X-CUSTOM-HEADER'));
        $this->assertTrue($req->hasHeader('x-custom-header'));
        $this->assertTrue($req->hasHeader('Content-Type'));
        $this->assertFalse($req->hasHeader('Non-Existent'));
    }
}
