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
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Chooses the format to user according to the Accept header and supported formats.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface AddFormatListenerInterface
{
    /**
     * Sets the applicable format to the HttpFoundation Request.
     *
     * @param GetResponseEvent $event
     *
     * @throws NotFoundHttpException
     * @throws NotAcceptableHttpException
     */
    public function onKernelRequest(GetResponseEvent $event);
}
