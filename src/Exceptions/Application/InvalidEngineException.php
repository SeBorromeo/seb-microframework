<?php namespace SeBorromeo\SebMicroframework\Exceptions\Application;

class InvalidEngineException extends \LogicException
{
    public function __construct(
        string|null $engine,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if ($engine)
            parent::__construct("View engine {$engine} is not supported.", $code, $previous);
        else
            parent::__construct("View engine must not be null. Set using \$app->set('view engine', {engine}). Options include: php and pug", $code, $previous);
    }
}
