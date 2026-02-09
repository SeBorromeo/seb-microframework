<?php namespace Sebastian\MicroFramework\Routing\Lib;

enum TokenType: string {
    case LBrace = '{';
    case RBrace = '}';
    case Wildcard = 'wildcard';
    case Param = 'param';
    case Char = 'char';
    case Escape = 'escape';
    case End = 'end';
}