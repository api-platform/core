<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
