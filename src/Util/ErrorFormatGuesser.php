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

namespace ApiPlatform\Core\Util;

use Symfony\Component\HttpFoundation\Request;

/**
 * Guesses the error format to use.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ErrorFormatGuesser
{
    private function __construct()
    {
    }

    /**
     * Get the error format and its associated MIME type.
     *
     * @param Request $request
     * @param array   $errorFormats
     *
     * @return array
     */
    public static function guessErrorFormat(Request $request, array $errorFormats): array
    {
        $requestFormat = $request->getRequestFormat('');

        if ('' !== $requestFormat && array_key_exists($requestFormat, $errorFormats)) {
            return ['key' => $requestFormat, 'value' => $errorFormats[$requestFormat]];
        }

        $mimeType = self::getMimeType($request);

        foreach ($errorFormats as $format => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes, true)) {
                return ['key' => $format, 'value' => $mimeTypes];
            }
        }

        return ['key' => key($errorFormats), 'value' => reset($errorFormats)];
    }

    /**
     * Get MIME type from the request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return string|null
     */
    private static function getMimeType(Request $request): ?string
    {
        if ($request->headers->has('accept') && '*/*' !== $request->headers->get('accept')) {
            return $request->headers->get('accept');
        }

        return $request->getMimeType($request->getContentType());
    }
}
