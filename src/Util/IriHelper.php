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
     * @throws InvalidArgumentException
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
     * @param float $page
     */
    public static function createIri(array $parts, array $parameters, string $pageParameterName, float $page = null, bool $absoluteUrl = false): string
    {
        if (null !== $page) {
            $parameters[$pageParameterName] = $page;
        }

        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $parts['query'] = preg_replace('/%5B\d+%5D/', '%5B%5D', $query);

        $url = '';

        if ($absoluteUrl && isset($parts['host'])) {
            if (isset($parts['scheme'])) {
                $url .= $parts['scheme'];
            } elseif (isset($parts['port']) && 443 === $parts['port']) {
                $url .= 'https';
            } else {
                $url .= 'http';
            }

            $url .= '://';

            if (isset($parts['user'])) {
                $url .= $parts['user'];

                if (isset($parts['pass'])) {
                    $url .= ':'.$parts['pass'];
                }

                $url .= '@';
            }

            $url .= $parts['host'];

            if (isset($parts['port'])) {
                $url .= ':'.$parts['port'];
            }
        }

        $url .= $parts['path'];

        if ('' !== $parts['query']) {
            $url .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }
}
