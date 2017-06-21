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

namespace ApiPlatform\Core\PathResolver;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\OperationTypeDeprecationHelper;
use Doctrine\Common\Inflector\Inflector;

/**
 * Generates a path with words separated by underscores.
 *
 * @author Paul Le Corre <paul@lecorre.me>
 */
final class UnderscoreOperationPathResolver implements OperationPathResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, $operationType/*, string $operationName = null*/): string
    {
        if (func_num_args() < 4) {
            @trigger_error(sprintf('Method %s() will have a 4th `string $operationName` argument in version 3.0. Not defining it is deprecated since 2.1.', __METHOD__), E_USER_DEPRECATED);
        }

        $operationType = OperationTypeDeprecationHelper::getOperationType($operationType);

        if ($operationType === OperationType::SUBRESOURCE && 1 < count($operation['identifiers'])) {
            $path = str_replace('.{_format}', '', $resourceShortName);
        } else {
            $path = '/'.Inflector::pluralize(Inflector::tableize($resourceShortName));
        }

        if ($operationType === OperationType::ITEM) {
            $path .= '/{id}';
        }

        if ($operationType === OperationType::SUBRESOURCE) {
            list($key) = end($operation['identifiers']);
            $property = true === $operation['collection'] ? Inflector::pluralize(Inflector::tableize($operation['property'])) : Inflector::tableize($operation['property']);
            $path .= sprintf('/{%s}/%s', $key, $property);
        }

        $path .= '.{_format}';

        return $path;
    }
}
