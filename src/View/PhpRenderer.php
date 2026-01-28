<?php

namespace Sebastian\MicroFramework\View;

use RuntimeException;

class PhpRenderer implements RendererInterface {
    public function __construct(
        private string $basePath
    ) {}

    public function render(string $view, array $data = []): string {
        // TODO: remove .php if already there
        $file = rtrim($this->basePath, '/') . '/' . $view . '.php';

        if (!is_file($file)) 
            throw new RuntimeException("View not found: {$view}");

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return ob_get_clean();
    }

    public function contentType(): string {
        return 'text/html; charset=utf-8';
    }
}
