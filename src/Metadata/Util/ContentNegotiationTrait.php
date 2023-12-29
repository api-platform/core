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

namespace ApiPlatform\Metadata\Util;

use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
trait ContentNegotiationTrait
{
    private Negotiator $negotiator;

    /**
     * Gets the format associated with the mime type.
     *
     * Adapted from {@see Request::getFormat}.
     *
     * @param array<string, string|string[]> $formats
     */
    private function getMimeTypeFormat(string $mimeType, array $formats): ?string
    {
        $canonicalMimeType = null;
        $pos = strpos($mimeType, ';');
        if (false !== $pos) {
            $canonicalMimeType = trim(substr($mimeType, 0, $pos));
        }

        foreach ($formats as $format => $mimeTypes) {
            if (\in_array($mimeType, $mimeTypes, true)) {
                return $format;
            }
            if (null !== $canonicalMimeType && \in_array($canonicalMimeType, $mimeTypes, true)) {
                return $format;
            }
        }

        return null;
    }

    /**
     * Flattened the list of MIME types.
     *
     * @param array<string, string|string[]> $formats
     *
     * @return array<string, string>
     */
    private function flattenMimeTypes(array $formats): array
    {
        $flattenedMimeTypes = [];
        foreach ($formats as $format => $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $flattenedMimeTypes[$mimeType] = $format;
            }
        }

        return $flattenedMimeTypes;
    }

    /**
     * @param array<string, string|string[]> $formats
     */
    private function getRequestFormat(Request $request, array $formats, bool $throw = true): string
    {
        $mimeTypes = [];
        $flattenedMimeTypes = [];

        if ($routeFormat = $request->attributes->get('_format') ?: null) {
            if (isset($formats[$routeFormat])) {
                $mimeTypes = Request::getMimeTypes($routeFormat);
                $flattenedMimeTypes = $this->flattenMimeTypes([$routeFormat => $mimeTypes]);
            } elseif ($throw) {
                throw new NotFoundHttpException(sprintf('Format "%s" is not supported', $routeFormat));
            }
        }

        if (!$mimeTypes) {
            $flattenedMimeTypes = $this->flattenMimeTypes($formats);
            $mimeTypes = array_keys($flattenedMimeTypes);
        }

        // First, try to guess the format from the Accept header
        /** @var string|null $accept */
        $accept = $request->headers->get('Accept');
        if (null !== $accept) {
            if ($mediaType = $this->negotiator->getBest($accept, $mimeTypes)) {
                return $this->getMimeTypeFormat($mediaType->getType(), $formats);
            }

            if ($throw) {
                throw $this->getNotAcceptableHttpException($accept, $flattenedMimeTypes);
            }
        }

        // Then use the Symfony request format if available and applicable
        $requestFormat = $request->getRequestFormat('') ?: null;
        if (null !== $requestFormat) {
            $mimeType = $request->getMimeType($requestFormat);

            if (isset($flattenedMimeTypes[$mimeType])) {
                return $requestFormat;
            }

            if ($throw) {
                throw $this->getNotAcceptableHttpException($mimeType, $flattenedMimeTypes);
            }
        }

        // Finally, if no Accept header nor Symfony request format is set, return the default format
        return array_key_first($formats);
    }

    /**
     * Retrieves an instance of NotAcceptableHttpException.
     */
    private function getNotAcceptableHttpException(string $accept, array $mimeTypes): NotAcceptableHttpException
    {
        return new NotAcceptableHttpException(sprintf(
            'Requested format "%s" is not supported. Supported MIME types are "%s".',
            $accept,
            implode('", "', array_keys($mimeTypes))
        ));
    }
}
