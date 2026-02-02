<?php namespace Sebastian\MicroFramework;

final class Application {
    protected array $settings = [
        'env' => 'development',
        'etag' => 'weak',
        'jsonp callback name' => 'callback',
        'query parser' => 'simple',
        'subdomain offset' => 2,
        'trust proxy' => false,
        'x-powered-by' => true
    ];

    public function set(string $key, mixed $value): void {
        $this->settings[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->settings[$key] ?? $default;
    }

    public function __construct() {
        $this->set('env', getenv('APP_ENV') ?: 'development');
        $this->set('views', getcwd() . '/views');
    }

    
}