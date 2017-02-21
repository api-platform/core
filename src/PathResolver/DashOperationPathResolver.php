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
 * Generates a path with words separated by dashes.
 *
 * @author Paul Le Corre <paul@lecorre.me>
 */
final class DashOperationPathResolver implements OperationPathResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveOperationPath(string $resourceShortName, array $operation, $operationType): string
    {
        $operationType = OperationTypeDeprecationHelper::getOperationType($operationType);

        if ($operationType === OperationType::SUBRESOURCE && 1 < count($operation['identifiers'])) {
            $path = str_replace('.{_format}', '', $resourceShortName);
        } else {
            $path = '/'.Inflector::pluralize($this->dashize($resourceShortName));
        }

        if ($operationType === OperationType::ITEM) {
            $path .= '/{id}';
        }

        if ($operationType === OperationType::SUBRESOURCE) {
            list($key) = end($operation['identifiers']);
            $property = true === $operation['collection'] ? Inflector::pluralize($this->dashize($operation['property'])) : $this->dashize($operation['property']);
            $path .= sprintf('/{%s}/%s', $key, $property);
        }

        $path .= '.{_format}';

        return $path;
    }

    private function dashize(string $string): string
    {
        return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '-$1', $string));
    }
}
