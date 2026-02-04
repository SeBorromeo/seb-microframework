<?php

namespace Sebastian\MicroFramework\View\Engine;

use Pug\Pug;

class PugEngine implements EngineInterface {
    private Pug $pug;

    public function __construct() {
        $this->pug = new Pug([
            'cache'   => false, // TODO: Figure this out
        ]);
    }

    public function render(string $file, array $data = []): string {
        return $this->pug->render($file, $data);
    }
}
