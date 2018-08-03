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

namespace ApiPlatform\Core\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Retrieves data from the applicable data provider and sets it as a request parameter called data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ReadListenerInterface
{
    /**
     * Calls the data provider and sets the data attribute.
     *
     * @param GetResponseEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onKernelRequest(GetResponseEvent $event);
}
