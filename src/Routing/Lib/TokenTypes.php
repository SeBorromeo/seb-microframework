<?php namespace Sebastian\MicroFramework\Routing\Lib;

const SIMPLE_TOKENS = [
    "{" => "{",
    "}" => "}",
];

enum TokenType: string {
    case LBrace = '{';
    case RBrace = '}';
    case Wildcard = 'wildcard';
    case Param = 'param';
    case Char = 'char';
    case Escape = 'escape';
    case End = 'end';
}


class LexToken {
    public function __construct(
        public readonly TokenType $type,
        public readonly int $index,
        public readonly string $value,
    ) {}
}

enum TokenNodeType: string {
    case Text = 'text';
    case Param = 'param';
    case Wildcard = 'wildcard';
    case Group = 'group';
}

abstract class Token {
    public function __construct(
        public readonly TokenType $type,
    ) {}
}

class Text extends Token {
    public function __construct(
        public readonly string $text
    ) {
        parent::__construct(TokenNodeType::Text);
    }
}

class Parameter extends Token {
    public function __construct(
        public readonly string $name
    ) {
        parent::__construct(TokenNodeType::Param);
    }
}

class Wildcard extends Token {
    public function __construct(
        public readonly string $name
    ) {
        parent::__construct(TokenNodeType::Wildcard);
    }
}

class Group extends Token {
    /** 
     * @var Token[] 
     */
    public readonly array $tokens;

    /**
     * @param Token[] $tokens
     */
    public function __construct(array $tokens) {
        $this->tokens = $tokens;
        parent::__construct(TokenNodeType::Group);
    }
}
