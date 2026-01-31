<?php namespace Sebastian\MicroFramework\View;

use Sebastian\MicroFramework\Exceptions\Http\ViewNotFoundException;

abstract class AbstractRenderer implements RendererInterface {
    public function __construct(
        protected string $basePath
    ) {}

    protected function resolveView(string $view, string $extension): string {
        // TODO: remove .extension if already there
        //       throw error if not valid extension
        $file = rtrim($this->basePath, '/') . '/' . $view . $extension;

        if (!is_file($file)) 
            throw new ViewNotFoundException($view);

        return $file;
    }
}
