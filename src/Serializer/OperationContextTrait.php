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

namespace ApiPlatform\Serializer;

use ApiPlatform\Metadata\ApiProperty;

/**
 * @internal
 */
trait OperationContextTrait
{
    /**
     * This context is created when working on a relation context or items of a collection. It cleans the previously given
     * context as the operation changes.
     */
    protected function createOperationContext(array $context, ?string $resourceClass = null, ?ApiProperty $propertyMetadata = null): array
    {
        if (isset($context['operation']) && !isset($context['root_operation'])) {
            $context['root_operation'] = $context['operation'];
        }

        if (isset($context['operation_name']) || isset($context['graphql_operation_name'])) {
            $context['root_operation_name'] = $context['operation_name'] ?? $context['graphql_operation_name'];
        }

        unset($context['iri'], $context['uri_variables'], $context['item_uri_template'], $context['force_resource_class']);

        // At some point we should merge the jsonld context here, there's a TODO to simplify this somewhere else
        if ($propertyMetadata) {
            $context['output'] ??= [];
            $context['output']['gen_id'] = $propertyMetadata->getGenId() ?? true;
        }

        if (!$resourceClass) {
            return $context;
        }

        if (($operation = $context['operation'] ?? null) && method_exists($operation, 'getItemUriTemplate')) {
            $context['item_uri_template'] = $operation->getItemUriTemplate();
        }

        unset($context['operation'], $context['operation_name']);
        $context['resource_class'] = $resourceClass;

        return $context;
    }
}
