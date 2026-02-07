<?php

use PHPUnit\Framework\TestCase;
use Sebastian\MicroFramework\Routing\Lib\PathUtils;

class PathUtilsTest extends TestCase {
    public function testEscapeText(): void {
        $this->assertEquals('/users/\:id', PathUtils::escapeText('/users/:id'));
        $this->assertEquals('/users/\:id\+', PathUtils::escapeText('/users/:id+'));
        $this->assertEquals('/users/\*', PathUtils::escapeText('/users/*'));
        $this->assertEquals('/users/\{id\}', PathUtils::escapeText('/users/{id}'));
        $this->assertEquals('/posts\(/\:year\(/\:month\)\)', PathUtils::escapeText('/posts(/:year(/:month))'));
        $this->assertEquals('/users/\:id\?', PathUtils::escapeText('/users/:id?'));
        $this->assertEquals('/search/query\\\\\\?', PathUtils::escapeText('/search/query\\?'));
    }

    public function testEscape(): void {
        $this->assertEquals('\#\[\\\\\#\.\+\*\?\^\$\{\}\(\)\[\\\\\\]\|\/\\\\\\\\\]\#', PathUtils::escape('#[\#.+*?^${}()[\]|/\\\\]#'));
    }
}   