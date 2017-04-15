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

use ApiPlatform\Core\Exception\InvalidArgumentException;

/**
 * Parses and creates IRIs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
final class IriHelper
{
    private function __construct()
    {
    }

    /**
     * Parses and standardizes the request IRI.
     *
     * @param string $iri
     * @param string $pageParameterName
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public static function parseIri(string $iri, string $pageParameterName): array
    {
        $parts = parse_url($iri);
        if (false === $parts) {
            throw new InvalidArgumentException(sprintf('The request URI "%s" is malformed.', $iri));
        }

        $parameters = [];
        if (isset($parts['query'])) {
            $parameters = RequestParser::parseRequestParams($parts['query']);

            // Remove existing page parameter
            unset($parameters[$pageParameterName]);
        }

        return ['parts' => $parts, 'parameters' => $parameters];
    }

    /**
     * Gets a collection IRI for the given parameters.
     *
     * @param array  $parts
     * @param array  $parameters
     * @param string $pageParameterName
     * @param float  $page
     *
     * @return string
     */
    public static function createIri(array $parts, array $parameters, string $pageParameterName, float $page = null): string
    {
        if (null !== $page) {
            $parameters[$pageParameterName] = $page;
        }

        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $parts['query'] = preg_replace('/%5B[0-9]+%5D/', '%5B%5D', $query);

        $url = $parts['path'];

        if ('' !== $parts['query']) {
            $url .= '?'.$parts['query'];
        }

        return $url;
    }
}
