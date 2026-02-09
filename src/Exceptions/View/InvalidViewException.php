<?php namespace Sebastian\MicroFramework\Exceptions\View;

class InvalidViewException extends \RuntimeException {
    public function __construct(
        string $view,
        string $extension,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct("Invalid view: {$view}. Expected extension: {$extension}", $code, $previous);
    }
}
