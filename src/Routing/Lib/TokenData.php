<?php namespace Sebastian\MicroFramework\Routing\Lib;

class TokenData {
    /** 
     * @var Token[] 
     */
    public readonly array $tokens;

    /**
     * @param Token[] $tokens
     */
    public function __construct(
        array $tokens,
        public readonly ?string $originalPath,
    ) {
        $this->tokens = $tokens;
    }
}