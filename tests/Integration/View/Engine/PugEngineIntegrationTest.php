<?php

use PHPUnit\Framework\TestCase;
use SeBorromeo\SebMicroframework\View\Engine\PugEngine;

class PugEngineIntegrationTest extends TestCase {
    public function testRendersValidPugFile(): void {
        $engine = new PugEngine();

        $result = $engine->render(
            __DIR__ . '/../../../fixtures/View/pug/with-data.pug', 
            ['title' => 'Hello']
        );

        $this->assertStringContainsString('<h1>Hello</h1>', $result);
    }

    public function testRendersWithoutData(): void {
        $engine = new PugEngine();

        $result = $engine->render(
            __DIR__ . '/../../../fixtures/View/pug/simple.pug'
        );

        $this->assertStringContainsString('<p>Static text</p>', $result);
    }
}