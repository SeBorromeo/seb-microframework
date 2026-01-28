<?php namespace Sebastian\MicroFramework;

final class Application {
    protected array $config = [];

    public function set(string $key, mixed $value): void {
        $this->config[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->config[$key] ?? $default;
    }

    public function __construct() {
        $this->set('view path', getcwd());
    }

}