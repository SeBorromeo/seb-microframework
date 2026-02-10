<?php namespace Sebastian\MicroFramework\Routing\Lib\Exception;

class PathException extends \InvalidArgumentException {
    public function __construct(
        string $message = "Invalid route path",
        public readonly ?string $originalPath = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if ($originalPath !== null) 
            $message .= ": " . $originalPath;
        
        parent::__construct($message, $code, $previous);
    }
}
