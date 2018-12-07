<?php


namespace ApiPlatform\Core\Event;


use Symfony\Component\EventDispatcher\Event;

class PreValidateEvent extends Event
{
    const NAME = Events::PRE_VALIDATE;
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
