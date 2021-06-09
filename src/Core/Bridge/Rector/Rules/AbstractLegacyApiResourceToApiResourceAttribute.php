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

namespace ApiPlatform\Core\Bridge\Rector\Rules;

use ApiPlatform\Core\Bridge\Rector\Resolver\OperationClassResolver;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Stmt\Class_;
use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Rector\Core\Rector\AbstractRector;
use Rector\PhpAttribute\Printer\PhpAttributeGroupFactory;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\String\UnicodeString;

/**
 * @experimental
 */
abstract class AbstractLegacyApiResourceToApiResourceAttribute extends AbstractRector
{
    use DeprecationMetadataTrait;

    protected PhpAttributeGroupFactory $phpAttributeGroupFactory;

    protected array $operationTypes = ['graphql', 'collectionOperations', 'itemOperations']; // operations will be added below #[ApiResource] in the reverse order : itemOperations, collectionOperations, then graphql
    protected array $defaultOperationsByType = [
        'itemOperations' => [
            'get',
            'put',
            'patch',
            'delete',
        ],
        'collectionOperations' => [
            'get',
            'post',
        ],
    ];

    private array $operations = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'];
    private array $graphQlOperations = ['item_query', 'collection_query', 'mutation'];

    protected function normalizeOperations(array $operations, string $type): array
    {
        foreach (array_reverse($operations) as $name => $arguments) {
            /*
             * Case of custom action, ex:
             * itemOperations={
             *     "get_by_isbn"={"method"="GET", "path"="/books/by_isbn/{isbn}.{_format}", "requirements"={"isbn"=".+"}, "identifiers"="isbn"}
             * }
             */
            if (\is_array($arguments)) {
                // add operation name
                $arguments = ['name' => $name] + $arguments;
                foreach ($arguments as $key => $argument) {
                    // camelize argument name
                    $camelizedKey = (string) (new UnicodeString($key))->camel();
                    if ($key === $camelizedKey) {
                        continue;
                    }
                    $arguments[$camelizedKey] = $argument;
                    unset($arguments[$key]);
                }
                // Prevent wrong order of operations
                unset($operations[$name]);
            }

            /*
             * Case of default action, ex:
             * collectionOperations={"get", "post"},
             * itemOperations={"get", "put", "delete"},
             * graphql={"create", "delete"}
             */
            if (\is_string($arguments)) {
                unset($operations[$name]);
                $name = $arguments;
                $arguments = ('graphql' !== $type) ? [] : ['name' => $arguments];
            }

            if (isset($arguments['name']) && \in_array(strtolower($arguments['name']), 'graphql' !== $type ? $this->operations : $this->graphQlOperations, true)) {
                unset($arguments['name']);
            }

            $operations[$name] = $arguments;
        }

        return $operations;
    }

    protected function createOperationAttributeGroup(string $type, string $name, array $arguments): AttributeGroup
    {
        $operationClass = OperationClassResolver::resolve($name, $type, $arguments);

        $camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
        // Replace old attributes with new attributes
        foreach ($arguments as $key => $value) {
            [$updatedKey, $updatedValue] = $this->getKeyValue($camelCaseToSnakeCaseNameConverter->normalize($key), $value);
            unset($arguments[$key]);
            $arguments[$updatedKey] = $updatedValue;
        }
        // remove unnecessary argument "method" after resolving the operation class
        if (isset($arguments['method'])) {
            unset($arguments['method']);
        }

        return $this->phpAttributeGroupFactory->createFromClassWithItems($operationClass, $arguments);
    }

    /**
     * @param Class_ $node
     */
    protected function resolveOperations($items, Node $node): array
    {
        $values = $items instanceof DoctrineAnnotationTagValueNode ? $items->getValues() : $items;

        foreach ($this->operationTypes as $type) {
            if (isset($values[$type])) {
                $operations = $this->normalizeOperations($items instanceof DoctrineAnnotationTagValueNode ? $values[$type]->getValuesWithExplicitSilentAndWithoutQuotes() : $values[$type], $type);
                foreach ($operations as $name => $arguments) {
                    array_unshift($node->attrGroups, $this->createOperationAttributeGroup($type, $name, $arguments));
                }

                if ('graphql' === $type && [] === $operations) {
                    $values['graphQlOperations'] = [];
                    continue;
                }

                if ($items instanceof DoctrineAnnotationTagValueNode) {
                    // Remove collectionOperations|itemOperations from Tag values
                    $items->removeValue($type);
                    $values = $items->getValues();
                    continue;
                }

                unset($values[$type]);
                continue;
            }

            // Add default operations if not specified
            if (\in_array($type, array_keys($this->defaultOperationsByType), true)) {
                foreach (array_reverse($this->defaultOperationsByType[$type]) as $operationName) {
                    array_unshift($node->attrGroups, $this->createOperationAttributeGroup($type, $operationName, []));
                }
            }
        }

        $camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();

        // Transform "attributes" keys
        if (isset($values['attributes'])) {
            $attributes = \is_array($values['attributes']) ? $values['attributes'] : $values['attributes']->values;
            foreach ($attributes as $attribute => $value) {
                $values[$camelCaseToSnakeCaseNameConverter->denormalize($attribute)] = $value;
            }

            unset($values['attributes']);
        }

        // Transform deprecated keys
        foreach ($values as $attribute => $value) {
            [$updatedAttribute, $updatedValue] = $this->getKeyValue(str_replace('"', '', $camelCaseToSnakeCaseNameConverter->normalize($attribute)), $value);
            if ($attribute !== $updatedAttribute) {
                $values[$updatedAttribute] = $updatedValue;
                unset($values[$attribute]);
            }
        }

        return $values;
    }
}
