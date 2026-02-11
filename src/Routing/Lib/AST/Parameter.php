<?php namespace Sebastian\MicroFramework\Routing\Lib\AST;

class Parameter extends Key {
    public function type(): string {
        return 'parameter';
    }
}
