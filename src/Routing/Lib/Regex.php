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

    public static function fromString(string $str, array|string $flags = []): Regex {
        $flags = is_string($flags) ? str_split($flags) : $flags;

        $invalid = array_diff($flags, self::ALLOWED_FLAGS);
        if ($invalid) 
            throw new \InvalidArgumentException('Invalid regex flag(s): ' . implode(', ', $invalid));

        $fullPattern = self::DEFAULT_DELIMITER . $str . self::DEFAULT_DELIMITER . implode('', $flags);

        return new self($fullPattern);
    }

    public function __toString(): string {
        return $this->pattern;
    }
}
