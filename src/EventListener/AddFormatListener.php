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

use ApiPlatform\Core\Api\FormatMatcher;
use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Chooses the format to use according to the Accept header and supported formats.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AddFormatListener
{
    private $negotiator;
    private $formats = [];
    private $mimeTypes;
    private $formatsProvider;
    private $formatMatcher;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(Negotiator $negotiator, /* FormatsProviderInterface */ $formatsProvider)
    {
        $this->negotiator = $negotiator;
        if (\is_array($formatsProvider)) {
            @trigger_error('Using an array as formats provider is deprecated since API Platform 2.3 and will not be possible anymore in API Platform 3', E_USER_DEPRECATED);
            $this->formats = $formatsProvider;
        } else {
            if (!$formatsProvider instanceof FormatsProviderInterface) {
                throw new InvalidArgumentException(sprintf('The "$formatsProvider" argument is expected to be an implementation of the "%s" interface.', FormatsProviderInterface::class));
            }

            $this->formatsProvider = $formatsProvider;
        }
    }

    /**
     * Sets the applicable format to the HttpFoundation Request.
     *
     * @throws NotFoundHttpException
     * @throws NotAcceptableHttpException
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!($request->attributes->has('_api_resource_class') || $request->attributes->getBoolean('_api_respond', false) || $request->attributes->getBoolean('_graphql', false))) {
            return;
        }
        // BC check to be removed in 3.0
        if (null !== $this->formatsProvider) {
            $this->formats = $this->formatsProvider->getFormatsFromAttributes(RequestAttributesExtractor::extractAttributes($request));
        }
        $this->formatMatcher = new FormatMatcher($this->formats);

        $this->populateMimeTypes();
        $this->addRequestFormats($request, $this->formats);

        // Empty strings must be converted to null because the Symfony router doesn't support parameter typing before 3.2 (_format)
        if (null === $routeFormat = $request->attributes->get('_format') ?: null) {
            $mimeTypes = array_keys($this->mimeTypes);
        } elseif (!isset($this->formats[$routeFormat])) {
            throw new NotFoundHttpException(sprintf('Format "%s" is not supported', $routeFormat));
        } else {
            $mimeTypes = Request::getMimeTypes($routeFormat);
        }

        // First, try to guess the format from the Accept header
        /** @var string|null $accept */
        $accept = $request->headers->get('Accept');
        if (null !== $accept) {
            if (null === $mediaType = $this->negotiator->getBest($accept, $mimeTypes)) {
                throw $this->getNotAcceptableHttpException($accept, $mimeTypes);
            }

            $request->setRequestFormat($this->formatMatcher->getFormat($mediaType->getType()));

            return;
        }

        // Then use the Symfony request format if available and applicable
        $requestFormat = $request->getRequestFormat('') ?: null;
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
     * Adds the supported formats to the request.
     *
     * This is necessary for {@see Request::getMimeType} and {@see Request::getMimeTypes} to work.
     */
    private function addRequestFormats(Request $request, array $formats): void
    {
        foreach ($formats as $format => $mimeTypes) {
            $request->setFormat($format, (array) $mimeTypes);
        }
    }

    /**
     * Populates the $mimeTypes property.
     */
    private function populateMimeTypes(): void
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
     * @param string[]|null $mimeTypes
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
