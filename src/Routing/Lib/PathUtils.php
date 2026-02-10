<?php namespace Sebastian\MicroFramework\Routing\Lib;

use Sebastian\MicroFramework\Routing\Lib\Exception\PathException;

const DEFAULT_DELIMITER = '/';
function noop($v) { return $v; }
const ID_START = '/^[$_\p{ID_Start}]$/u';
const ID_CONTINUE = '/^[$\x{200C}\x{200D}\p{ID_Continue}]$/u';

const SIMPLE_TOKENS = [
    "{" => TokenType::LBrace,
    "}" => TokenType::RBrace,
];

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
        public readonly TokenNodeType $type,
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

class PathUtils {  
    /**
     * Escape a regular expression string.
     */
    private static function escape(string $str): string {
        return preg_replace('#[\#.+*?^${}()[\]|/\\\\]#', '\\\\$0', $str);
    }

    /* ---------- Parse ---------- */

    /**
     * Parse a path string into tokens. 
     * 
     * @param string $string 
     *  - The path string to parse
     * 
     * @param array $options {
     *   encodePath?: callable(string): string
     * }
     *  - Optional settings
     * 
     * @return TokenData
     */
    public static function parse(string $string, array $options = []): TokenData {
        $encodePath = $options['encodePath'] ?? noop(...);
        $chars = str_split($string);
        $tokens = [];
        $index = 0;
        $pos = 0;

        while ($index < count($chars)) {
            $value = $chars[$index];
            $type = SIMPLE_TOKENS[$value] ?? null;

            if ($type) {
                $tokens[] = new LexToken($type, $index++, $value);
            } else if ($value === '\\' ) {
                $tokens[] = new LexToken(TokenType::Escape, $index++, $chars[$index++]);
            } else if ($value === ':') {
                $tokens[] = new LexToken(TokenType::Param, $index++, self::parseParamName($chars, $index));
            } else if ($value === '*') {
                $tokens[] = new LexToken(TokenType::Wildcard, $index++, self::parseParamName($chars, $index));
            } else {
                $tokens[] = new LexToken(TokenType::Char, $index++, $value);
            }
        }

        $tokens[] = new LexToken(TokenType::End, $index, '');
        
        return new TokenData(self::consumeUntil(TokenType::End, $tokens, $encodePath, $pos), $string);
    }

    /**
     * Consume tokens until a token of the given type is found, returning the consumed tokens and the index of the end token.
     * 
     * @param TokenType $endType
     * 
     * @param LexToken[] $tokens
     * 
     * @param callable(string): string $encodePath
     * 
     * @return Token[]
     */
    private static function consumeUntil(TokenType $endType, array $tokens, callable $encodePath, int &$pos): array {
        $output = [];

        while (true) {
            $token = $tokens[$pos++];
            if ($token->type === $endType) 
                break;
            
            if ($token->type === TokenType::Char || $token->type === TokenType::Escape) {
                $path = $token->value;
                $cur = $tokens[$pos];

                while ($cur->type === TokenType::Char || $cur->type === TokenType::Escape) {
                    $path .= $cur->value;
                    $cur = $tokens[++$pos];
                }

                $output[] = new Text($encodePath($path));
            } else if ($token->type === TokenType::Param) {
                $output[] = new Parameter($token->value);
            } else if ($token->type === TokenType::Wildcard) {
                $output[] = new Wildcard($token->value);
            } else if ($token->type === TokenType::LBrace) {
                $output[] = new Group(self::consumeUntil(TokenType::RBrace, $tokens, $encodePath, $pos));
            } else {
                throw new PathException("Unexpected token type {$token->type} at index {$token->index}, expected $endType");
            }
        }

        return $output;
    }

    /**
     * Parse a parameter name from the path string as a char array, starting at the given index.
     * 
     * @param string[] $chars
     *  - The path string as an array of characters
     * 
     * @param int $index
     *  - The current index in the char array (passed by reference, will be updated to the position after the parsed name)
     * 
     * @return string
     */
    private static function parseParamName(array $chars, int &$index): string {
        $value = '';

        if (preg_match(ID_START, $chars[$index])) {
            do {
                $value .= $chars[$index++];
            } while (preg_match(ID_CONTINUE, $chars[$index]));
        } else if ($chars[$index] === '"') {
            $quoteStart = $index;
            while ($index++ < count($chars)) {
                if ($chars[$index] === '"') {
                    $index++;
                    $quoteStart = 0;
                    break;
                }

                if ($chars[$index] === '\\') 
                    $index++;

                $value .= $chars[$index];
            }

            if ($quoteStart) 
                throw new PathException("Unterminated quote at index $quoteStart");
        } 

        if (!$value) 
            throw new PathException("Missing parameter name at index $index");

        return $value;
    }
    public static function decodeParam(string $val): string {
        $decoded = rawurldecode($val);
        if (!mb_check_encoding($decoded, 'UTF-8')) 
           throw new \InvalidArgumentException("Failed to decode param '$val'", 400);

        return $decoded;
    }

    public static function loosen(array|string $path): string {
        if ($path === '/') {
            return $path;
        }

        return is_array($path) ? array_map([self::class, 'loosen'], $path) : rtrim($path, '/');
    }
    /**
     * Block backtracking on previous text and ignore delimiter string.
     */
    private static function negate(string $delimiter, string $backtrack): string {
        $del = self::escape($delimiter);
        $bt  = self::escape($backtrack);

        if (strlen($backtrack) < 2) {
            if (strlen($delimiter) < 2)
                return "[^$del$bt]";

            return "(?:(?!$del)[^$bt])";
        } else if (strlen($delimiter) < 2) {
            return "(?:(?!$bt)[^$del])";
        }
        return "(?:(?!$del|$bt)[\\s\\S])";
    }

    /* ---------- Stringify ---------- */

    /**
     * Escape text for stringify to path.
     */
    private static function escapeText(string $str): string {
        return preg_replace('/[{}()\[\]+?!:*\\\\]/', '\\\\$0', $str);
    }
}