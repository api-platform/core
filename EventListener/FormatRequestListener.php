<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\EventListener;

use Negotiation\FormatNegotiatorInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Chooses the format to user according to the Accept header and supported formats.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class FormatRequestListener
{
    /**
     * @var FormatNegotiatorInterface
     */
    private $formatNegotiator;
    /**
     * @var string[]
     */
    private $supportedFormats;

    /**
     * @param FormatNegotiatorInterface $formatNegotiator
     * @param string[]                  $supportedFormats
     */
    public function __construct(FormatNegotiatorInterface $formatNegotiator, $supportedFormats)
    {
        $this->formatNegotiator = $formatNegotiator;
        $this->supportedFormats = $supportedFormats;
    }

    /**
     * Assign the format to use to the _api_format Request attribute.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->attributes->get('_resource_type')) {
            return;
        }

        // Use the Symfony request format if available and applicable
        $format = $request->getRequestFormat(null);
        if (null === $format || !in_array($format, $this->supportedFormats)) {
            if (null !== $accept = $request->headers->get('Accept')) {
                // Try to guess the best format to use
                $format = $this->formatNegotiator->getBestFormat($accept, $this->supportedFormats);
            }
        }

        $request->attributes->set('_api_format', $format ?: $this->supportedFormats[0]);
    }
}
