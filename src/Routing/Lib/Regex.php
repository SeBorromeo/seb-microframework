<?php namespace Sebastian\MicroFramework\Routing\Lib;

class Regex {
    public const DEFAULT_DELIMITER = '#';
    public const ALLOWED_FLAGS = ['i','m','s','x','u'];

    private string $pattern;

    public function __construct(string $pattern, array $flags = []) {
        $pattern = str_replace(self::DEFAULT_DELIMITER, '\\' . self::DEFAULT_DELIMITER, $pattern);

        foreach ($flags as $f) {
            if (!in_array($f, self::ALLOWED_FLAGS, true)) {
                throw new \InvalidArgumentException("Invalid regex flag: $f");
            }
        }

        $flagStr = implode('', $flags);

        $fullPattern = self::DEFAULT_DELIMITER . $pattern . self::DEFAULT_DELIMITER . $flagStr;

        if (@preg_match($fullPattern, '') === false) {
            throw new \InvalidArgumentException("Invalid regex pattern: $fullPattern");
        }

        $this->pattern = $fullPattern;
    }

    public function __toString(): string {
        return $this->pattern;
    }
}
