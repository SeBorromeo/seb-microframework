<?php

namespace Sebastian\MicroFramework\View;

use Pug\Pug;
use Sebastian\MicroFramework\Exceptions\Http\ViewNotFoundException;

class PugRenderer implements RendererInterface {
    private Pug $pug;

    public function __construct(private string $basePath) {
        $this->pug = new Pug([
            'basedir' => $this->basePath,
            'cache'   => false, // TODO: Figure this out
        ]);
    }

    public function render(string $view, array $data = []): string {
        $file = rtrim($this->basePath, '/') . '/' . $view . '.pug';

        if (!is_file($file)) {
            throw new ViewNotFoundException($view);
        }

        return $this->pug->render($file, $data);
    }

    public function contentType(): string {
        return 'text/html; charset=utf-8';
    }
}
