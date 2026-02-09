<?php namespace Sebastian\MicroFramework\Routing\Lib\Exception;

class PathException extends \InvalidArgumentException {
    public function __construct(
        string $message = "Invalid route path",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
