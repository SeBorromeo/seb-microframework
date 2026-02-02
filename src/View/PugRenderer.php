<?php

namespace Sebastian\MicroFramework\View;

use Pug\Pug;
use Sebastian\MicroFramework\Exceptions\Http\ViewNotFoundException;

class PugRenderer extends AbstractRenderer {
    private Pug $pug;

    public function __construct(string $basePath) {
        parent::__construct($basePath, '.pug');
        $this->pug = new Pug([
            'basedir' => $basePath,
            'cache'   => false, // TODO: Figure this out
        ]);
    }

    public function render(string $view, array $data = []): string {
        $file = $this->resolveView($view);
        return $this->pug->render($file, $data);
    }

    public function contentType(): string {
        return 'text/html; charset=utf-8';
    }
}
