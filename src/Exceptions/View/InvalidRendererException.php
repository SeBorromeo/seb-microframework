<?php

namespace Sebastian\MicroFramework\Exceptions\Http;

class InvalidRendererException extends \LogicException
{
    public function __construct(
        string $message = 'Headers have already been sent.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
