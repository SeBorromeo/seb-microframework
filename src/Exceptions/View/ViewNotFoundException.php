<?php

namespace Sebastian\MicroFramework\Exceptions\Http;

class ViewNotFoundException extends \RuntimeException // (is this a better parent class?)
{
    public function __construct(
        string $view,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct("View not found: {$view}", $code, $previous);
    }
}
