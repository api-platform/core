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

namespace ApiPlatform\Core\Bridge\Symfony\Messenger;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\ClassInfoTrait;

/**
 * Transforms an Input to itself. This gives the ability to send the Input to a
 * message handler and process it asynchronously.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class DataTransformer implements DataTransformerInterface
{
    use ClassInfoTrait;

    /**
     * @var ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface
     */
    private $resourceMetadataFactory;

    public function __construct($resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return object
     */
    public function transform($object, string $to, array $context = [])
    {
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if (
            \is_object($data) // data is not normalized yet, it should be an array
            ||
            null === ($context['input']['class'] ?? null)
        ) {
            return false;
        }

        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            try {
                $resourceMetadataCollection = $this->resourceMetadataFactory->create($context['resource_class'] ?? $to);
                $operation = $resourceMetadataCollection->getOperation($context['operation_name'] ?? null);

                return 'input' === $operation->getMessenger();
            } catch (OperationNotFoundException $e) {
                return false;
            }
        }

        $metadata = $this->resourceMetadataFactory->create($context['resource_class'] ?? $to);

        if (isset($context['graphql_operation_name'])) {
            return 'input' === $metadata->getGraphqlAttribute($context['graphql_operation_name'], 'messenger', null, true);
        }

        if (!isset($context['operation_type'])) {
            return 'input' === $metadata->getAttribute('messenger');
        }

        return 'input' === $metadata->getTypedOperationAttribute(
            $context['operation_type'],
            $context[$context['operation_type'].'_operation_name'] ?? '',
            'messenger',
            null,
            true
        );
    }
}
