<?php

use PHPUnit\Framework\TestCase;
use Sebastian\MicroFramework\Routing\Lib\PathUtils;

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
    }
}   