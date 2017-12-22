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

namespace ApiPlatform\Core\Graphql\Resolver\Factory;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Graphql\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a function resolving a GraphQL mutation of an item.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ItemMutationResolverFactory implements ResolverFactoryInterface
{
    private $iriConverter;
    private $dataPersister;
    private $normalizer;
    private $resourceMetadataFactory;

    public function __construct(IriConverterInterface $iriConverter, DataPersisterInterface $dataPersister, NormalizerInterface $normalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException(sprintf('The normalizer must implements the "%s" interface', DenormalizerInterface::class));
        }

        $this->iriConverter = $iriConverter;
        $this->dataPersister = $dataPersister;
        $this->normalizer = $normalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function __invoke(string $resourceClass = null, string $rootClass = null, string $operationName = null): callable
    {
        return function ($root, $args, $context, ResolveInfo $info) use ($resourceClass, $operationName) {
            $data = ['clientMutationId' => $args['input']['clientMutationId'] ?? null];
            $item = null;

            if (isset($args['input']['id'])) {
                try {
                    $item = $this->iriConverter->getItemFromIri($args['input']['id']);
                } catch (ItemNotFoundException $e) {
                    throw Error::createLocatedError(sprintf('Item "%s" not found.', $args['input']['id']), $info->fieldNodes, $info->path);
                }
            }

            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            switch ($operationName) {
                case 'create':
                case 'update':
                    $context = null === $item ? ['resource_class' => $resourceClass] : ['resource_class' => $resourceClass, 'object_to_populate' => $item];
                    $item = $this->normalizer->denormalize($args['input'], $resourceClass, ItemNormalizer::FORMAT, $context);
                    $this->dataPersister->persist($item);

                    return $this->normalizer->normalize(
                        $item,
                        ItemNormalizer::FORMAT,
                        $resourceMetadata->getGraphqlAttribute($operationName, 'normalization_context', [], true)
                    ) + $data;

                case 'delete':
                    if ($item) {
                        $this->dataPersister->remove($item);
                        $data['id'] = $args['input']['id'];
                    } else {
                        $data['id'] = null;
                    }
            }

            return $data;
        };
    }
}
