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

    private function __construct()
    {
    }

    /**
     * Generates a Symfony route name.
     *
     * @param string      $operationName
     * @param string      $resourceShortName
     * @param string|bool $operationType
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public static function generate(string $operationName, string $resourceShortName, $operationType): string
    {
        if (OperationType::SUBRESOURCE === $operationType = OperationTypeDeprecationHelper::getOperationType($operationType)) {
            throw new InvalidArgumentException(sprintf('%s::SUBRESOURCE is not supported as operation type by %s().', OperationType::class, __METHOD__));
        }

        return sprintf(
            '%s%s_%s_%s',
            static::ROUTE_NAME_PREFIX,
            Inflector::pluralize(Inflector::tableize($resourceShortName)),
            $operationName,
            $operationType
        );
    }
}
