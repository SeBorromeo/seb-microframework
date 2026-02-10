<?php

use PHPUnit\Framework\TestCase;
use Sebastian\MicroFramework\Routing\Lib\PathUtils;
use Sebastian\MicroFramework\Routing\Lib\Group;
use Sebastian\MicroFramework\Routing\Lib\Parameter;
use Sebastian\MicroFramework\Routing\Lib\Text;
use Sebastian\MicroFramework\Routing\Lib\Wildcard;

class PathUtilsTest extends TestCase {
    public function testEscapeText(): void {
        $method = new ReflectionMethod(PathUtils::class, 'escapeText');

        $this->assertEquals('/users/\:id', $method->invoke(null, '/users/:id'));
        $this->assertEquals('/users/\:id\+', $method->invoke(null, '/users/:id+'));
        $this->assertEquals('/users/\*', $method->invoke(null, '/users/*'));
        $this->assertEquals('/users/\{id\}', $method->invoke(null, '/users/{id}'));
        $this->assertEquals('/posts\(/\:year\(/\:month\)\)', $method->invoke(null, '/posts(/:year(/:month))'));
        $this->assertEquals('/users/\:id\?', $method->invoke(null, '/users/:id?'));
        $this->assertEquals('/search/query\\\\\\?', $method->invoke(null, '/search/query\\?'));
    }

    public function testEscape(): void {
        $method = new ReflectionMethod(PathUtils::class, 'escape');

        $this->assertEquals('\#\[\\\\\#\.\+\*\?\^\$\{\}\(\)\[\\\\\\]\|\/\\\\\\\\\]\#', $method->invoke(null, '#[\#.+*?^${}()[\]|/\\\\]#'));
    }

    /* ---------- Parse ---------- */

    public function testParseText(): void {
        $result = PathUtils::parse('/users/list');

        $this->assertCount(1, $result->tokens);
        $this->assertInstanceOf(Text::class, $result->tokens[0]);
        $this->assertSame('/users/list', $result->tokens[0]->value);
    }

    public function testParseParameter(): void {
        $result = PathUtils::parse('/users/:id');

        $this->assertCount(2, $result->tokens);
        $this->assertInstanceOf(Text::class, $result->tokens[0]);
        $this->assertSame('/users/', $result->tokens[0]->text);
        $this->assertInstanceOf(Parameter::class, $result->tokens[1]);
        $this->assertSame('id', $result->tokens[1]->name);
    }

    public function testParseWildcard(): void {
        $result = PathUtils::parse('/files/*filepath');

        $this->assertCount(2, $result->tokens);
        $this->assertInstanceOf(Text::class, $result->tokens[0]);
        $this->assertSame('/files/', $result->tokens[0]->value);
        $this->assertInstanceOf(Wildcard::class, $result->tokens[1]);
        $this->assertSame('filepath', $result->tokens[1]->name);
    }

    public function testParseGroup(): void {
        $result = PathUtils::parse('/posts{/:year{/:month}}');

        $this->assertCount(2, $result->tokens);
        $this->assertInstanceOf(Text::class, $result->tokens[0]);
        $this->assertSame('/posts', $result->tokens[0]->value);
        $this->assertInstanceOf(Group::class, $result->tokens[1]);

        $group1 = $result->tokens[1];
        $this->assertCount(3, $group1->tokens);
        $this->assertInstanceOf(Parameter::class, $group1->tokens[1]);
        $this->assertSame('year', $group1->tokens[1]->name);
        $this->assertInstanceOf(Group::class, $group1->tokens[2]);

        $group2 = $group1->tokens[2];
        $this->assertCount(2, $group2->tokens);
        $this->assertInstanceOf(Parameter::class, $group2->tokens[1]);
        $this->assertSame('month', $group2->tokens[1]->name);
    }

    /* ---------- Match ---------- */

    public function testMatchParam(): void {
        $result = PathUtils::match('/users/:id');

        var_dump($result);
        var_dump($result('/users/123'));

        $this->assertNotFalse($result('/users/123'));
        $this->assertEquals(['/users/123', ['id' => ['123']]], $result('/users/123'));
        $this->assertFalse($result('/users/'));
        $this->assertFalse($result('/users/123/profile'));
    }

    public function testMatchWildcard(): void {
        $result = PathUtils::match('/files/*filepath');

        $this->assertNotFalse($result('/files/images/photo.jpg'));
        $this->assertEquals(['/files/images/photo.jpg', ['filepath' => ['images', 'photo.jpg']]], $result('/files/images/photo.jpg'));
        $this->assertEquals(['/files/docs/report.pdf', ['filepath' => ['docs', 'report.pdf']]], $result('/files/docs/report.pdf'));
        $this->assertFalse($result('/files/'));
        $this->assertFalse($result('/files'));
    }

    public function testMatchOptionalGroup(): void {
        $result = PathUtils::match('/posts{/:year{/:month}}');

        $this->assertEquals(['/posts', []], $result('/posts'));
        $this->assertEquals(['/posts/2023', ['year' => ['2023']]], $result('/posts/2023'));
        $this->assertEquals(['/posts/2023/06', ['year' => ['2023'], 'month' => ['06']]], $result('/posts/2023/06'));
        $this->assertFalse($result('/posts/2023/06/extra'));
    }
}   