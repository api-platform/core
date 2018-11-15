<?php


namespace ApiPlatform\Core\Event;


use Symfony\Component\EventDispatcher\Event;

final class PreDeserializeEvent extends Event
{
    const NAME = ApiPlatformEvents::PRE_DESERIALIZE;
    private $controllerResult;

    public function __construct($controllerResult)
    {
        $this->controllerResult = $controllerResult;
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
