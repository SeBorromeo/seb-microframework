<?php namespace Sebastian\MicroFramework\Routing\Lib\AST;

abstract class Key extends FlatToken {
    public function __construct(
        public readonly string $name
    ) {}
}
