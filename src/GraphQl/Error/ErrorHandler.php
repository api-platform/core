<?php


namespace ApiPlatform\Core\GraphQl\Error;


class ErrorHandler implements ErrorHandlerInterface
{
    public function __invoke(array $errors, callable $formatter)
    {
        return array_map($formatter, $errors);
    }
}
