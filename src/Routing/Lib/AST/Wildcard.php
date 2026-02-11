<?php namespace Sebastian\MicroFramework\Routing\Lib\AST;

class Wildcard extends Key {
    public function type(): string {
        return 'wildcard';
    }
}
