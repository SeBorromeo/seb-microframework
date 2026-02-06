<?php namespace Sebastian\MicroFramework\Routing\Lib;

class Regex {
    private string $pattern;

    public function __construct(string $pattern) {
        if (@preg_match($pattern, '') === false) 
            throw new \InvalidArgumentException("Invalid regex pattern: $pattern");

        $this->pattern = $pattern;
    }

    public function __toString(): string {
        return $this->pattern;
    }
}
