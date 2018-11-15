<?php

namespace ApiPlatform\Core\Event;

use Symfony\Component\EventDispatcher\Event;

final class PreReadEvent extends Event
{
    const NAME = ApiPlatformEvents::PRE_READ;

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
