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
