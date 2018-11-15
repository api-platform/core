<?php


namespace ApiPlatform\Core\Event;


use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

final class PostRespondEvent extends Event
{
    const NAME = ApiPlatformEvents::POST_RESPOND;

    private $event;

    public function __construct(GetResponseForControllerResultEvent $event)
    {
        $this->event = $event;
    }

    public function getEvent(): GetResponseForControllerResultEvent
    {
        return $this->event;
    }

    public function setEvent(GetResponseForControllerResultEvent $event): void
    {
        $this->event = $event;
    }
}
