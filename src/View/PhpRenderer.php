<?php namespace Sebastian\MicroFramework\View;

class PhpRenderer extends AbstractRenderer {
    public function __construct(string $basePath) {
        parent::__construct($basePath, '.php');
    }

    public function render(string $view, array $data = []): string {
        $file = $this->resolveView($view);

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return ob_get_clean();
    }

    public function contentType(): string {
        return 'text/html; charset=utf-8';
    }
}
