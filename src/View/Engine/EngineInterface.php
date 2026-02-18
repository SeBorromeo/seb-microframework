<?php

namespace SeBorromeo\SebMicroframework\View\Engine;

interface EngineInterface {
    public function render(string $file, array $data = []): string;
}
