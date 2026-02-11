<?php namespace Sebastian\MicroFramework\Routing\Lib;

use Sebastian\MicroFramework\Routing\Lib\AST\Group;
use Sebastian\MicroFramework\Routing\Lib\AST\Key;
use Sebastian\MicroFramework\Routing\Lib\AST\Parameter;
use Sebastian\MicroFramework\Routing\Lib\AST\Text;
use Sebastian\MicroFramework\Routing\Lib\AST\Token;
use Sebastian\MicroFramework\Routing\Lib\AST\Wildcard;
use Sebastian\MicroFramework\Routing\Lib\AST\TokenData;
use Sebastian\MicroFramework\Routing\Lib\Exception\PathException;
use Sebastian\MicroFramework\Routing\Lib\Lexer\LexToken;
use Sebastian\MicroFramework\Routing\Lib\Lexer\TokenType;

const DEFAULT_DELIMITER = '/';
function noop($v) { return $v; }
const ID_START = '/^[$_\p{ID_Start}]$/u';
const ID_CONTINUE = '/^[$\x{200C}\x{200D}\p{ID_Continue}]$/u';

const SIMPLE_TOKENS = [
    "{" => TokenType::LBrace,
    "}" => TokenType::RBrace,
    // Reserved for use
    "(" => TokenType::LParen,
    ")" => TokenType::RParen,
    "[" => TokenType::LBracket,
    "]" => TokenType::RBracket,
    "+" => TokenType::Plus,
    "?" => TokenType::Optional,
    "!" => TokenType::Exclamation,
];

class PathUtils {  
    /* ---------- Parse ---------- */

    /**
     * Parse a path string into tokens. 
     * 
     * @param string $string 
     *  - The path string to parse
     * 
     * @param array{
     *   encodePath?: callable(string): string
     * } $options
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
     *  - The path string as an array of characters.
     * 
     * @param int $index
     *  - The current index in the char array (passed by reference, will be updated to the position after the parsed name).
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
                throw new PathException("Unterminated quote at index $quoteStart", implode('', $chars));
        } 

        if (!$value) 
            throw new PathException("Missing parameter name at index $index", implode('', $chars));

        return $value;
    }

    /* ---------- Compile ---------- */

    // public function compile(Path $path, array $options = []): callable {
    //     return function() {};
    // }

    private function tokensToFunction(array $tokens, string $delimiter, callable|false $encode): callable {
        return function() {}; // TODO
    }

    private function tokenToFunction(Token $token, string $delimiter, callable|false $encode): callable {
        return function() {}; // TODO

    }

    /* ---------- Match ---------- */

    /**
     * Transform a path into a match function
     * 
     * @param string|array $path
     *  - A path string or an array of path strings to match against.
     * 
     * @param array{
     *     decode?: callable(string): string,
     *     delimiter?: string
     * } $options
     * 
     * @return callable(string): array|false
     */
    public static function match(string|array $path, array $options = []): callable {
        $decode    = $options['decode']    ?? [PathUtils::class, 'decodeURIComponent'];
        $delimiter = $options['delimiter'] ?? DEFAULT_DELIMITER;

        ['regex' => $regex, 'keys' => $keys] = self::pathToRegex($path, $options);
    
        $decoders = array_map(function(Key $key) use ($decode, $delimiter) { 
            if (!$decode)
                return noop(...);

            if ($key->type() === 'param')
                return $decode;
            
            return fn(string $value) => array_map($decode, explode($delimiter, $value));
        }, $keys);

        return function(string $p) use ($regex, $keys, $decoders): array|false {
            preg_match($regex, $p, $matches);
            if (!$matches) 
                return false;
            
            $path = $matches[0];
            $params = [];

            for ($i = 1; $i < count($matches); $i++) {
                if (is_null($matches[$i])) 
                    continue;

                $key = $keys[$i - 1];
                $decoder = $decoders[$i - 1];
                $params[$key->name] = $decoder($matches[$i]);
            }

            return ['path' => $path, 'params' => $params];
        };
    }

    /**
     * Convert a path string with parameters into a regex pattern and extract parameter keys.
     * 
     * @param string|array $path
     *  - A path string or an array of path strings to convert into regex patterns.
     * 
     * @param array{
     *   delimiter?: string,
     *   end?: bool,
     *   sensitive?: bool,
     *   trailing?: bool
     * } $options
     *  - An array of options to control the regex generation.
     *    - delimiter: The delimiter to use for matching path segments (default: DEFAULT_DELIMITER).
     *    - end: Whether to match the end of the path (default: true).
     *    - sensitive: Whether to generate a case-sensitive regex (default: false).
     *    - trailing: Whether to allow trailing delimiters (default: true).
     * 
     * @return array{regex: Regex, keys: Parameter[]}
      *  - An array containing the compiled regex pattern and an array of parameter keys.
     */
    public static function pathToRegex(string|array $path, array $options = []): array {
        $delimiter = $options['delimiter'] ?? DEFAULT_DELIMITER;
        $end       = $options['end']       ?? true;
        $sensitive = $options['sensitive'] ?? false;
        $trailing  = $options['trailing']  ?? true;

        /** @var Parameter[] */
        $keys = [];
        $flags = $sensitive ? '' : 'i';
        $sources = [];

        foreach (self::pathsToArray($path, []) as $input) {
            $data = $input instanceof TokenData ? $input : self::parse($input, $options);
            foreach (self::flatten($data->tokens) as $tokens) {
                $sources[] = self::toRegexSource($tokens, $delimiter, $keys, $data->originalPath);
            }
        }

        $pattern = "^(?:" . implode('|', $sources) . ")";
        if ($trailing) {
            $pattern .= "(?:" . preg_quote($delimiter) . "$)?";
        }
        $pattern .= $end ? '$' : '(?=' . preg_quote($delimiter) . '|$)';
        
        $regex = Regex::fromString($pattern, $flags);
        return ['regex' => $regex, 'keys' => $keys];
    }

    /** 
     * Convert a path or array of paths into a flat array of paths.
     * 
     * @param string|array $paths
     *  - A single path string or an array of path strings
     * 
     * @param array $init
     *  - An initial array to accumulate paths into (used for recursion)
     * 
     * @return array
     */
    private static function pathsToArray(string|array $paths, array $init): array {
        if (is_array($paths)) {
            foreach ($paths as $p) {
                self::pathsToArray($p, $init);
            }
        } else {
            $init[] = $paths;
        }
        return $init;
    }

    /**
     * Flatten a nested array of tokens into an array of token sequences, where each sequence represents a possible path through the token tree. Used to generate regex patterns for all combinations of optional groups.
     * 
     * For example, the path "/posts{/:year{/:month}}" would produce the following token sequences:
     *  - /posts
     *  - /posts/:year
     *  - /posts/:year/:month
     * 
     * @param Token[] $tokens
     * 
     * @param int $index
     * - The current index in the tokens array (used for recursion).
     * 
     * @param array $init
      *  - An initial array of tokens accumulated so far (used for recursion).
     * 
     * @return iterable
      *  - An iterable of token sequences, where each sequence is an array of tokens representing a possible path through the token tree.
     */
    private static function flatten(array $tokens, int $index = 0, array $init = []): iterable {
        if ($index === count($tokens)) 
            return yield $init;

        $token = $tokens[$index];

        if ($token instanceof Group) {
            yield from self::flatten($tokens, $index + 1, $init);

            foreach (self::flatten($token->tokens, 0, $init) as $seq) {
                yield from self::flatten($tokens, $index + 1, $seq);
            }

            return;
        }

        $init[] = $token;
        yield from self::flatten($tokens, $index + 1, $init);
    }

    /**
     * Transform a flat sequence of tokens into a regular expression.
     * 
     * @param FlatToken[] $tokens
     * 
     * @param string $delimiter
     * 
     * @param Key[] $keys
     *  - An array to accumulate parameter keys into (passed by reference).
     * 
     * @param string|null $originalPath
     * 
     * @return string
     * 
     * @throws PathException 
     *  - If a parameter token is not preceded by any text, which would make it impossible to determine where a previous parameter/wildcard value ends in the path string.
     */
    private static function toRegexSource(array $tokens, string $delimiter, array &$keys, ?string $originalPath): string {
        $result = '';
        $backtrack = '';
        $isSafeSegmentParam = true;

        foreach ($tokens as $token) {
            if ($token instanceof Text) {
                $result .= preg_quote($token->value);
                $backtrack .= $token->value;
                $isSafeSegmentParam = $isSafeSegmentParam || str_contains($token->value, $delimiter);
                continue;
            } 

            if ($token instanceof Key) {
                if (!$isSafeSegmentParam && !$backtrack) 
                    throw new PathException("Missing text before $token->name " . $token->type(), $originalPath);

                if ($token instanceof Parameter) {
                    $result .= '(' . self::negate($delimiter, $isSafeSegmentParam ? '' : $backtrack) . '+)';
                } else {
                    $result .= '([\\s\\S]+)';
                }

                $keys[] = $token;
                $backtrack = '';
                $isSafeSegmentParam = false;
                continue;
            }
        }

        return $result;
    }

    /**
     * Create regex section that blocks backtracking on previous text and ignores delimiter string.
     * 
     * @param string $delimiter
     *  - The delimiter string to ignore (e.g. '/').
     * 
     * @param string $backtrack
     *  - The previous text to block backtracking on.
     * 
     * @return string
     */
    private static function negate(string $delimiter, string $backtrack): string {
        $del = preg_quote($delimiter);
        $bt  = preg_quote($backtrack);

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
     * Stringify token data into a path string.
     */
    public static function stringify(TokenData $data): string {
        return self::stringifyTokens($data->tokens);
    }

    /**
     * Stringify an array of tokens into a path string.
     */
    private static function stringifyTokens(array $tokens): string {
        return ''; //TODO
    } 

    /**
     * Escape text for stringify to path.
     */
    private static function escapeText(string $str): string {
        return preg_replace('/[{}()\[\]+?!:*\\\\]/', '\\\\$0', $str);
    }

    private static function isNameSafe(string $name): bool {
        return false; //TODO
    }

    private static function isNextNameSafe(?Token $token = null): bool {
        return false; //TODO
    }

    /* ---------- Helper ---------- */
    
    /**
     * Decode a URI component.
     * 
     * @throws \InvalidArgumentException
     *  - If the decoded value is not valid UTF-8.
     */
    private static function decodeURIComponent(string $val): string {
        $decoded = rawurldecode($val);
        if (!mb_check_encoding($decoded, 'UTF-8')) 
           throw new \InvalidArgumentException("Failed to decode param '$val'");

        return $decoded;
    }
}