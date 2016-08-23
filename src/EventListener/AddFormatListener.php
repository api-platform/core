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

use Negotiation\Exception\InvalidMediaType;
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

        // Empty strings must be converted to null because the Symfony router doesn't support parameter typing before 3.2 (_format)
        $routeFormat = $request->attributes->get('_format') ?: null;
        $originalRequestFormat = $request->getRequestFormat(null) ?: null;

        // First, try to guess the format from the Accept header
        $accept = $request->headers->get('Accept');
        if (null !== $accept) {
            try {
                if (null === $acceptHeader = $this->negotiator->getBest($accept, array_keys($this->mimeTypes))) {
                    throw $this->getNotAcceptableHttpException($accept);
                }
            } catch (InvalidMediaType $e) {
                throw new NotAcceptableHttpException(sprintf('The "%s" MIME type is invalid.', $accept));
            }

            $mimeType = $acceptHeader->getType();
            $requestFormat = $request->getFormat($mimeType);

            if (null !== $routeFormat && $requestFormat !== $routeFormat) {
                throw $this->getNotAcceptableHttpException($accept, $request->getMimeTypes($routeFormat));
            }

            $request->setRequestFormat($requestFormat);

            return;
        }

        // Then use the Symfony request format if available and applicable
        if (null !== $originalRequestFormat) {
            $mimeType = $request->getMimeType($originalRequestFormat);

            if (isset($this->mimeTypes[$mimeType])) {
                return;
            }

            throw $this->getNotAcceptableHttpException($mimeType);
        }

        // Finally, if no Accept header nor Symfony request format is set, return the default format
        reset($this->formats);
        $request->setRequestFormat(each($this->formats)['key']);
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

    /**
     * Retrieves an instance of NotAcceptableHttpException.
     *
     * @param string        $accept
     * @param string[]|null $mimeTypes
     *
     * @return NotAcceptableHttpException
     */
    private function getNotAcceptableHttpException(string $accept, array $mimeTypes = null) : NotAcceptableHttpException
    {
        if (null === $mimeTypes) {
            $mimeTypes = array_keys($this->mimeTypes);
        }

        return new NotAcceptableHttpException(sprintf(
            'Requested format "%s" is not supported. Supported MIME types are "%s".',
            $accept,
            implode('", "', $mimeTypes)
        ));
    }
}
