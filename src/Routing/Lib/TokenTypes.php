<?php namespace Sebastian\MicroFramework\Routing\Lib;

enum TokenType: string {
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
        parent::__construct(TokenType::Text);
    }
}

class Parameter extends Token {
    public function __construct(
        public readonly string $name
    ) {
        parent::__construct(TokenType::Param);
    }
}

class Wildcard extends Token {
    public function __construct(
        public readonly string $name
    ) {
        parent::__construct(TokenType::Wildcard);
    }
}

class Group extends Token {
    /** 
     * @var Token[] 
     */
    public readonly array $tokens;

    public function __construct(array $tokens) {
        $this->tokens = $tokens;
        parent::__construct(TokenType::Group);
    }
}