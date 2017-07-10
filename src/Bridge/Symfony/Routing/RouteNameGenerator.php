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

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\OperationTypeDeprecationHelper;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Util\Inflector;

/**
 * Generates the Symfony route name associated with an operation name and a resource short name.
 *
 * @internal
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class RouteNameGenerator
{
    const ROUTE_NAME_PREFIX = 'api_';
    const SUBRESOURCE_SUFFIX = '_subresource';

    private function __construct()
    {
    }

    /**
     * Generates a Symfony route name.
     *
     * @param string      $operationName
     * @param string      $resourceShortName
     * @param string|bool $operationType
     * @param array       $subresourceContext
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public static function generate(string $operationName, string $resourceShortName, $operationType, array $subresourceContext = []): string
    {
        if (OperationType::SUBRESOURCE === $operationType = OperationTypeDeprecationHelper::getOperationType($operationType)) {
            if (!isset($subresourceContext['property'])) {
                throw new InvalidArgumentException('Missing "property" to generate a route name from a subresource');
            }

            return sprintf(
              '%s%s_%s_%s%s',
              static::ROUTE_NAME_PREFIX,
              self::routeNameResolver($resourceShortName),
              self::routeNameResolver($subresourceContext['property'], $subresourceContext['collection'] ?? false),
              $operationName,
              self::SUBRESOURCE_SUFFIX
            );
        }

        return sprintf(
            '%s%s_%s_%s',
            static::ROUTE_NAME_PREFIX,
            self::routeNameResolver($resourceShortName),
            $operationName,
            $operationType
        );
    }

    /**
     * Transforms a given string to a tableized, pluralized string.
     *
     * @param string $name usually a ResourceMetadata shortname
     *
     * @return string A string that is a part of the route name
     */
    public static function routeNameResolver(string $name, bool $pluralize = true): string
    {
        $name = Inflector::tableize($name);

        return $pluralize ? Inflector::pluralize($name) : $name;
    }
}
