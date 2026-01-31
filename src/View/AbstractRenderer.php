<?php namespace Sebastian\MicroFramework\View;

use InvalidArgumentException;
use Sebastian\MicroFramework\Exceptions\Http\InvalidViewException;
use Sebastian\MicroFramework\Exceptions\Http\ViewNotFoundException;

abstract class AbstractRenderer implements RendererInterface {
    public function __construct(
        protected string $basePath,
        protected string $extension
    ) {
        $this->extension = $this->normalizeExtension($extension);
    }

    private function normalizeExtension(string $extension): string {
        $extension = trim($extension);

        if ($extension === '') 
            throw new InvalidArgumentException('View extension cannot be empty.');

        $extension = ltrim($extension, '.');

        if (!preg_match('/^[a-zA-Z0-9]+$/', $extension))
            throw new InvalidArgumentException("Invalid view extension: {$extension}");

        return '.' . $extension;
    }


    protected function resolveView(string $view): string {
        if (str_contains($view, '.')) {
            if (!str_ends_with($view, $this->extension)) 
                throw new InvalidViewException($view, $this->extension);

            $view = substr($view, 0, -strlen($this->extension));
        }

        $file = rtrim($this->basePath, '/') . '/' . $view . $this->extension;

        if (!is_file($file)) 
            throw new ViewNotFoundException($view);

        return $file;
    }
}
