<?php

namespace Sebastian\MicroFramework\View;

interface RendererInterface {
    public function render(string $view, array $data = []): string;
    
    public function contentType(): string;
}
