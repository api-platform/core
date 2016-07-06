<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\EventListener;

use Negotiation\Negotiator;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Chooses the format to user according to the Accept header and supported formats.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class FormatListener
{
    private $negotiator;
    private $formats;

    public function __construct(Negotiator $negotiator, array $formats)
    {
        $this->negotiator = $negotiator;
        $this->formats = $formats;
    }

    /**
     * Assigns the format to use to the _api_format Request attribute.
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (null === $request->attributes->get('_api_resource_class')) {
            return;
        }

        // Use the Symfony request format if available and applicable
        $requestFormat = $request->getRequestFormat(null);
        $mimeType = $requestFormat ? $request->getMimeType($requestFormat) : null;
        if (null === $mimeType || !in_array($mimeType, $this->formats)) {
            if (null === $accept = $request->headers->get('Accept')) {
                $mimeType = null;
            } else {
                // Try to guess the best format to use
                $acceptHeader = $this->negotiator->getBest($accept, array_keys($this->formats));
                $mimeType = $acceptHeader ? $acceptHeader->getType() : null;
            }
        }

        if ($mimeType) {
            $request->attributes->set('_api_mime_type', $mimeType);
            $request->attributes->set('_api_format', $this->formats[$mimeType]);

            return;
        }

        reset($this->formats);
        $format = each($this->formats);

        $request->attributes->set('_api_mime_type', $format['key']);
        $request->attributes->set('_api_format', $format['value']);
    }
}
