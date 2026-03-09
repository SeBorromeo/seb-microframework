<?php

use PHPUnit\Framework\TestCase;
use SeBorromeo\SebMicroframework\Routing\Router;

class RouterTest extends TestCase {
    public function testCreateRestoreFunctionPropsUnchanged(): void {
        $router = new Router();
    
        $obj = new class {
            public string $a = 'originalA';
            public string $b = 'originalB';
        };

        $fn = function () use ($obj) {
            return [$obj->a, $obj->b];
        };

        $restore = $router::createRestoreFunction($fn, $obj, 'a', 'b');

        $obj->a = 'changedA';
        $obj->b = 'changedB';

        $result = $restore();

        $this->assertEquals(['originalA', 'originalB'], $result);
    }

    public function testRestoreFunctionWithModifyingFunction(): void {
        $router = new Router();

        $obj = new class {
            public int $value = 10;
        };

        $fn = function ($x) use ($obj) {
            return $obj->value + $x;
        };

        $restore = $router::createRestoreFunction($fn, $obj, 'value');
        
        $obj->value = 20;

        $result = $restore(5);

        $this->assertEquals(15, $result);
    }
} 