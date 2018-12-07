<?php


namespace ApiPlatform\Core\Event;


use Symfony\Component\EventDispatcher\Event;

class PostValidateEvent extends Event
{
    const NAME = Events::POST_VALIDATE;
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }
}
