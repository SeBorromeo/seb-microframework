<?php

namespace Sebastian\MicroFramework\Exceptions\Http;

class ResponseAlreadySentException extends \LogicException {
    public function __construct(
        string $message = 'Response has already been sent.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
