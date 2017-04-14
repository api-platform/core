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

use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * @param GetResponseEvent $event
     *
     * @throws NotFoundHttpException
     * @throws NotAcceptableHttpException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->attributes->has('_api_resource_class') && !$request->attributes->has('_api_respond')) {
            return;
        }

        $this->populateMimeTypes();
        $this->addRequestFormats($request, $this->formats);

        // Empty strings must be converted to null because the Symfony router doesn't support parameter typing before 3.2 (_format)
        if (null === $routeFormat = $request->attributes->get('_format') ?: null) {
            $mimeTypes = array_keys($this->mimeTypes);
        } elseif (!isset($this->formats[$routeFormat])) {
            throw new NotFoundHttpException('Not Found');
        } else {
            $mimeTypes = Request::getMimeTypes($routeFormat);
        }

        // First, try to guess the format from the Accept header
        $accept = $request->headers->get('Accept');
        if (null !== $accept) {
            if (null === $acceptHeader = $this->negotiator->getBest($accept, $mimeTypes)) {
                throw $this->getNotAcceptableHttpException($accept, $mimeTypes);
            }

            $request->setRequestFormat($request->getFormat($acceptHeader->getType()));

            return;
        }

        // Then use the Symfony request format if available and applicable
        $requestFormat = $request->getRequestFormat(null) ?: null;
        if (null !== $requestFormat) {
            $mimeType = $request->getMimeType($requestFormat);

            if (isset($this->mimeTypes[$mimeType])) {
                return;
            }

            throw $this->getNotAcceptableHttpException($mimeType);
        }

        // Finally, if no Accept header nor Symfony request format is set, return the default format
        foreach ($this->formats as $format => $mimeType) {
            $request->setRequestFormat($format);

            return;
        }
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
    private function getNotAcceptableHttpException(string $accept, array $mimeTypes = null): NotAcceptableHttpException
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
