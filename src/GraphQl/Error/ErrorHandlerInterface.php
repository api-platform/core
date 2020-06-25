<?php


namespace ApiPlatform\Core\GraphQl\Error;


interface ErrorHandlerInterface
{
    public function __invoke(array $errors, callable $formatter);
}
