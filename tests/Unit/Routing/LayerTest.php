<?php

use PHPUnit\Framework\TestCase;
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
        $layer = new Layer('/users/:id');

        $this->assertTrue($layer->match('/users/123'));
        $this->assertSame('/users/123', $layer->path);
        $this->assertSame(['id' => ['123']], $layer->params);
    }

    public function testMatchWithWildcard(): void {
        $layer = new Layer('/files/*filepath');

        $this->assertTrue($layer->match('/files/images/photo.jpg'));
        $this->assertSame('/files/images/photo.jpg', $layer->path);
        $this->assertSame(['filepath' => ['images', 'photo.jpg']], $layer->params);
    }

    public function testMatchWithRegex(): void {
        $layer = new Layer(new Regex('/^\/users\/(\d+)$/'));

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
}