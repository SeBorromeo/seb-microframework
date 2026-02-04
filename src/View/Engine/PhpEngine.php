<?php

namespace Sebastian\MicroFramework\View\Engine;

class PhpEngine implements EngineInterface {
    public function render(string $file, array $data = []): string {
        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return ob_get_clean();
    }
}
