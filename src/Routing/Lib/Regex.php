<?php namespace Sebastian\MicroFramework\Routing\Lib;

class Regex {
    public const DEFAULT_DELIMITER = '#';
    public const ALLOWED_FLAGS = ['i','m','s','x','u'];

    private string $pattern;

    public function __construct(string $pattern) {
        if (@preg_match($pattern, '') === false) 
            throw new \InvalidArgumentException("Invalid regex pattern: $pattern");

        $this->pattern = $pattern;
    }

    public static function createWithStringLiteral(string $str, array $flags = []): Regex {
        $escaped = preg_quote($str, self::DEFAULT_DELIMITER);

        foreach ($flags as $f) {
            if (!in_array($f, self::ALLOWED_FLAGS, true)) 
                throw new \InvalidArgumentException("Invalid regex flag: $f");
        }

        $flagStr = implode('', $flags);

        $fullPattern = self::DEFAULT_DELIMITER . $escaped . self::DEFAULT_DELIMITER . $flagStr;

        return new self($fullPattern);
    }

    public function __toString(): string {
        return $this->pattern;
    }
}
