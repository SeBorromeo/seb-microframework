<?php namespace Sebastian\MicroFramework\Routing\Lib\AST;

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
    }

    public function type(): string {
        return 'group';
    }
}
