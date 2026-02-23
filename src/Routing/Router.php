<?php namespace SeBorromeo\SebMicroframework\Routing;

class Router {
    /** @var Layer[] */
    private array $stack = [];

    /** @var array<string, callable> */
    private array $params = [];
    
    private bool $caseSensitive;
    private bool $mergeParams;
    private bool $strict;

    public function __construct(
        private array $options = []
    ) {
        $this->caseSensitive = $options['case sensitive'] ?? false;
        $this->mergeParams = $options['merge params'] ?? false;
        $this->strict = $options['strict'] ?? false;
    }
}