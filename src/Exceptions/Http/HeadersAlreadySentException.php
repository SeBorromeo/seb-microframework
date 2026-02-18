<?php namespace SeBorromeo\SebMicroframework\Exceptions\Http;

class HeadersAlreadySentException extends \LogicException {
    public function __construct(
        string $message = 'Headers have already been sent.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
