<?php

namespace ApiPlatform\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class PreWriteEvent extends Event
{
    const NAME = ApiPlatformEvents::PRE_WRITE;
    private $method;
    private $controllerResult;

    public function __construct(string $method, $controllerResult)
    {
        $this->method = $method;
        $this->controllerResult = $controllerResult;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getControllerResult()
    {
        return $this->controllerResult;
    }

    public function setControllerResult($controllerResult): void
    {
        $this->controllerResult = $controllerResult;
    }
}
