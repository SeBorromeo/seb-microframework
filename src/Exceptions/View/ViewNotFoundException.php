<?php namespace Sebastian\MicroFramework\Exceptions\View;

class ViewNotFoundException extends \RuntimeException {
    public function __construct(
        string $view,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct("View not found: {$view}", $code, $previous);
    }
}
