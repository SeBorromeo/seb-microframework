<?php namespace Sebastian\MicroFramework\Routing\Lib\Lexer;

enum TokenType: string {
    case LBrace = '{';
    case RBrace = '}';
    case Wildcard = 'wildcard';
    case Param = 'param';
    case Char = 'char';
    case Escape = 'escape';
    case End = 'end';
    // Reserved for use
    case LParen = '(';
    case RParen = ')';
    case LBracket = '[';
    case RBracket = ']';
    case Plus = '+';    
    case Optional = '?';
    case Exclamation = '!';
}