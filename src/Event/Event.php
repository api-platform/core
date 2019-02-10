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

use Symfony\Component\EventDispatcher\Event as BaseEvent;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
abstract class Event extends BaseEvent implements EventInterface
{
    private $data;
    private $context;

    public function __construct($data, array $context = [])
    {
        $this->data = $data;
        $this->context = $context;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }
}
