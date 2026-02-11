<?php namespace Sebastian\MicroFramework\Routing\Lib\AST;

abstract class Token {
    abstract public function type(): string;
}