<?php

use PHPUnit\Framework\TestCase;
use Sebastian\MicroFramework\Http\Request;

class RequestTest extends TestCase {
    public function testMagicProperties(): void {
        $req = Request::createFromGlobals();

        $req->userId = 1;
        $this->assertSame(1, $req->userId);
        
        $req->setAttribute('userId', 2);
        $this->assertSame(2, $req->getAttribute('userId'));

        $this->assertTrue(isset($req->userId));
        unset($req->userId);
        $this->assertFalse(isset($req->userId));

        $this->expectException(\LogicException::class);
        $req->method = 'This shouldnt be possible';
    }

    public function testQueryParsing(): void {

    }

    public function testHeaderParsing(): void {
        $server = [
            'HTTP_X_CUSTOM_HEADER' => 'custom-value',
            'CONTENT_TYPE' => 'application/json',
        ];

        $req = new Request($server);

        $this->assertSame('custom-value', $req->header('X-CUSTOM-HEADER'));
        $this->assertSame('custom-value', $req->header('x-custom-header'));
        $this->assertSame('application/json', $req->header('Content-Type'));
        $this->assertNull($req->header('Non-Existent'));
    }
}
