<?php namespace SeBorromeo\SebMicroframework\Routing;

/**
 * Represents the result of matching a request path against a Layer.
 */
final class MatchResult {
    /**
     * @param array<string, string> $params
     *   Associative array of route parameters extracted from the path.
     *   Example: ['id' => '42'].
     * 
     * @param string $path
     *   The matched path segment for this layer.
     * 
     * @param string[] $keys
     *   List of parameter keys extracted from the route pattern.
     */
    public function __construct(
        public readonly array $params,
        public readonly string $path,
        public readonly array $keys,
    ) {}
}
