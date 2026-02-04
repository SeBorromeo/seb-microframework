<?php

namespace Sebastian\MicroFramework\View\Engine;

interface EngineInterface {
    public function render(string $file, array $data = []): string;
}
