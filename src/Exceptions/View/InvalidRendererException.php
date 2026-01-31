<?php namespace Sebastian\MicroFramework\Exceptions\Http;

class InvalidRendererException extends \LogicException
{
    public function __construct(
        string $engine,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct("Renderer engine {$engine} is not supported.", $code, $previous);
    }
}
