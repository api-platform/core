<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Routing;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Util\RequestParser;

/**
 * URL generator for collections.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class CollectionRoutingHelper
{
    /**
     * Parses and standardizes the request URI.
     *
     * @throws InvalidArgumentException
     */
    public static function parseRequestUri(string $requestUri, string $pageParameterName) : array
    {
        $parts = parse_url($requestUri);
        if (false === $parts) {
            throw new InvalidArgumentException(sprintf('The request URI "%s" is malformed.', $requestUri));
        }

        $parameters = [];
        if (isset($parts['query'])) {
            $parameters = RequestParser::parseRequestParams($parts['query']);

            // Remove existing page parameter
            unset($parameters[$pageParameterName]);
        }

        return [$parts, $parameters];
    }

    /**
     * Gets a collection IRI for the given parameters.
     */
    public static function generateUrl(array $parts, array $parameters, string $pageParameterName, float $page = null) : string
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
