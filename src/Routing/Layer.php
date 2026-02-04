<?php namespace Sebastian\MicroFramework\Routing;

class Layer {
    public function __construct(
        private string $method
    ) {}

    public function method(): string { return $this->method; }
}