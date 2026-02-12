<?php

use PHPUnit\Framework\TestCase;
use Sebastian\MicroFramework\View\Engine\PhpEngine;

class PhpEngineIntegrationTest extends TestCase {
    public function testRendersValidPhpFile(): void {
        $engine = new PhpEngine();

        $result = $engine->render(
            __DIR__ . '/../../../fixtures/View/php/with-data.php',
            ['title' => 'Hello']
        );

        $this->assertStringContainsString('<h1>Hello</h1>', $result);
    }

    public function testRendersWithoutData(): void {
        $engine = new PhpEngine();

        $result = $engine->render(
            __DIR__ . '/../../../fixtures/View/php/simple.php'
        );

        $this->assertStringContainsString('<p>Static text</p>', $result);
    }
}
