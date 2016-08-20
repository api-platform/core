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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Chooses the format to user according to the Accept header and supported formats.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddFormatListener
{
    private $negotiator;
    private $formats;
    private $mimeTypes;

    public function __construct(Negotiator $negotiator, array $formats)
    {
        $this->negotiator = $negotiator;
        $this->formats = $formats;
    }

    /**
     * Sets the applicable format to the HttpFoundation Request.
     *
     * @param $event GetResponseEvent
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (null === $request->attributes->get('_api_resource_class') && !$request->attributes->get('_api_respond')) {
            return;
        }

        $this->populateMimeTypes();
        $this->addRequestFormats($request, $this->formats);

        // Use the Symfony request format if available and applicable
        $requestFormat = $request->getRequestFormat(null);
        $mimeType = $requestFormat ? $request->getMimeType($requestFormat) : null;
        if (null === $mimeType || !isset($this->mimeTypes[$mimeType])) {
            if (null === $accept = $request->headers->get('Accept')) {
                if (null !== $requestFormat) {
                    throw $this->getNotAcceptableHttpException(null === $accept ? 'unknown' : $accept);
                }

                $mimeType = null;
            } else {
                // Try to guess the best format to use
                if (null === $acceptHeader = $this->negotiator->getBest($accept, array_keys($this->mimeTypes))) {
                    throw $this->getNotAcceptableHttpException($accept);
                }

                $mimeType = $acceptHeader->getType();
            }
        }

        if ($mimeType) {
            $format = $request->getFormat($mimeType);
            $request->setRequestFormat($format);

            return;
        }

        reset($this->formats);
        $format = each($this->formats);

        $request->setRequestFormat($format['key']);
    }

    /**
     * Adds API formats to the HttpFoundation Request.
     *
     * @param Request $request
     * @param array   $formats
     */
    private function addRequestFormats(Request $request, array $formats)
    {
        foreach ($formats as $format => $mimeTypes) {
            $request->setFormat($format, $mimeTypes);
        }
    }

    /**
     * Populates the $mimeTypes property.
     */
    private function populateMimeTypes()
    {
        if (null !== $this->mimeTypes) {
            return;
        }

        $this->mimeTypes = [];
        foreach ($this->formats as $format => $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $this->mimeTypes[$mimeType] = $format;
            }
        }
    }

    private function getNotAcceptableHttpException(string $accept)
    {
        return new NotAcceptableHttpException(sprintf(
            'Requested format "%s" is not supported. Supported MIME types are "%s".',
            $accept,
            implode('", "', array_keys($this->mimeTypes))
        ));
    }
}
