<?php

use PHPUnit\Framework\TestCase;
use SeBorromeo\SebMicroframework\Http\Response;
use SeBorromeo\SebMicroframework\Application;
use SeBorromeo\SebMicroframework\Exceptions\Application\InvalidEngineException;
use SeBorromeo\SebMicroframework\View\Engine\PhpEngine;

class ResponseTest extends TestCase {
    private Application $appMock;

    protected function setUp(): void {
        parent::setUp();
        $this->appMock = $this->createMock(Application::class);
    }

    /* ---------- Headers ---------- */

    public function testHeadersStartEmpty(): void {
        $res = new Response($this->appMock);

        $this->assertEquals([], $res->getHeaders());
        $this->assertEquals([], $res->getHeaderNames());
        $this->assertNull($res->getHeader('Content-Type'));
        $this->assertFalse($res->hasHeader('Content-Type'));
    }

    public function testSetHeader(): void {
        $res = new Response($this->appMock);
        $res->set('Accept', ['text/html', 'application/json', 'image/png']);

        $this->assertEquals(['text/html', 'application/json', 'image/png'], $res->getHeader('Accept'));
        $this->assertTrue($res->hasHeader('Accept'));
        $this->assertContains('accept', $res->getHeaderNames());
    }

    public function testAppendHeaderStrToStr(): void {
        $res = new Response($this->appMock);
        $res->set('Link', '<http://localhost/>');
        $res->append('Link', '<http://localhost:3000/>');

        $this->assertEquals(
            ['link' => ['<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testAppendHeaderListToStr(): void {
        $res = new Response($this->appMock);
        $res->set('Link', '<http://localhost:8000/>');
        $res->append('Link', ['<http://localhost/>', '<http://localhost:3000/>']);

        $this->assertEquals(
            ['link' => ['<http://localhost:8000/>', '<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testAppendHeaderListToList(): void {
        $res = new Response($this->appMock);
        $res->set('Link', ['<http://localhost:8000/>']);
        $res->append('Link', ['<http://localhost/>', '<http://localhost:3000/>']);

        $this->assertEquals(
            ['link' => ['<http://localhost:8000/>', '<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testAppendHeaderStrToList(): void {
        $res = new Response($this->appMock);
        $res->set('Link', ['<http://localhost:8000/>', '<http://localhost/>']);
        $res->append('Link', '<http://localhost:3000/>');

        $this->assertEquals(
            ['link' => ['<http://localhost:8000/>', '<http://localhost/>', '<http://localhost:3000/>']],
            $res->getHeaders()
        );
    }

    public function testRemoveHeader(): void {
        $res = new Response($this->appMock);
        $res->set('Accept', ['text/html']);
        $res->removeHeader('Accept');

        $this->assertFalse($res->hasHeader('Accept'));
        $this->assertNotContains('Accept', $res->getHeaderNames());
    }

    /* ---------- Header Helpers ---------- */

    public function testAttachment(): void {
        $res = new Response($this->appMock);

        $res->attachment('file.txt');

        $this->assertEquals('attachment; filename="file.txt"', $res->getHeader('Content-Disposition'));
        $this->assertEquals('text/plain', $res->getHeader('Content-Type'));
    }

    public function testFormat(): void {
    
    }

    public function testLinks(): void {
        $res = new Response($this->appMock);

        $res->links([
            'next' => 'http://api.example.com/users?page=2',
            'prev' => 'http://api.example.com/users?page=1',
        ]);

        $this->assertEquals([
            '<http://api.example.com/users?page=2>; rel="next"',
            '<http://api.example.com/users?page=1>; rel="prev"',
        ], $res->getHeader('Link'));
    }

    public function testLocation(): void {
        $res = new Response($this->appMock);
        
        $res->location('/login');

        $this->assertEquals('/login', $res->getHeader('Location'));
    }

    public function testType(): void {
        $res = new Response($this->appMock);

        $res->type('json');
        $this->assertEquals('application/json', $res->getHeader('Content-Type'));
        
        $res->type('text/plain');
        $this->assertEquals('text/plain', $res->getHeader('Content-Type'));
    }

    public function testVaryDuplicateValue(): void {
        $res = new Response($this->appMock);

        $res->vary('Accept-Encoding');
        $res->vary('Accept-Encoding');

        var_dump($res->getHeader('Vary'));

        $this->assertEquals('Accept-Encoding', $res->getHeader('Vary'));
    }

    /* ---------- Status ---------- */

    public function testDefaultStatus(): void {
        $res = new Response($this->appMock);
        $this->assertSame(200, $res->statusCode());
        $this->assertSame('OK', $res->statusMessage());
    }
        
    public function testSetStatusAndDefaultMessage(): void {
        $res = new Response($this->appMock);
        $res->status(404);

        $this->assertSame(404, $res->statusCode());
        $this->assertSame('Not Found', $res->statusMessage());
    }
    
    /* ---------- Cookies ---------- */


    /* ---------- View ---------- */

    public function testAddLocalViewVariable(): void {
        $res = new Response($this->appMock);
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

    public function testRenderThrowsWhenViewDirNotConfigured(): void {
        $this->appMock->method('get')
            ->with('views')
            ->willReturn(null);

        $res = new Response($this->appMock);
        $this->expectException(\LogicException::class);
        $res->render('index.php', ['name' => 'Sebastian']);
    }

    public function testRenderThrowsWhenRendererNull(): void {
        $this->appMock->method('get')
            ->with('view engine')
            ->willReturn(null);

        $res = new Response($this->appMock);
        $this->expectException(InvalidEngineException::class);
        $this->expectExceptionMessage('View engine must not be null.');
        $res->render('index', ['name' => 'Sebastian']);
    }

    /* ---------- Send ---------- */

    public function testHeadersSent(): void {

    }

    public function testRequestEnded(): void {

    }
}
