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

namespace ApiPlatform\Core\GraphQl\Resolver\Factory;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\GraphQl\Resolver\FieldsToAttributesTrait;
use ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Core\GraphQl\Resolver\ResourceAccessCheckerTrait;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use ApiPlatform\Core\Validator\Exception\ValidationException;
use ApiPlatform\Core\Validator\ValidatorInterface;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Psr\Container\ContainerInterface;
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
    use ClassInfoTrait;
    use FieldsToAttributesTrait;
    use ResourceAccessCheckerTrait;

    private $iriConverter;
    private $dataPersister;
    private $mutationResolverLocator;
    private $normalizer;
    private $resourceMetadataFactory;
    private $resourceAccessChecker;
    private $validator;

    public function __construct(IriConverterInterface $iriConverter, DataPersisterInterface $dataPersister, ContainerInterface $mutationResolverLocator, NormalizerInterface $normalizer, ResourceMetadataFactoryInterface $resourceMetadataFactory, ResourceAccessCheckerInterface $resourceAccessChecker = null, ValidatorInterface $validator = null)
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException(sprintf('The normalizer must implements the "%s" interface', DenormalizerInterface::class));
        }

        $this->iriConverter = $iriConverter;
        $this->dataPersister = $dataPersister;
        $this->mutationResolverLocator = $mutationResolverLocator;
        $this->normalizer = $normalizer;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceAccessChecker = $resourceAccessChecker;
        $this->validator = $validator;
    }

    public function __invoke(string $resourceClass = null, string $rootClass = null, string $operationName = null): callable
    {
        return function ($source, $args, $context, ResolveInfo $info) use ($resourceClass, $operationName) {
            if (null === $resourceClass) {
                return null;
            }

            $data = ['clientMutationId' => $args['input']['clientMutationId'] ?? null];
            $item = null;

            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $wrapFieldName = lcfirst($resourceMetadata->getShortName());
            $normalizationContext = $resourceMetadata->getGraphqlAttribute($operationName ?? '', 'normalization_context', [], true);
            $normalizationContext['attributes'] = $this->fieldsToAttributes($info)[$wrapFieldName] ?? [];

            if (isset($args['input']['id'])) {
                try {
                    $item = $this->iriConverter->getItemFromIri($args['input']['id'], $normalizationContext);
                } catch (ItemNotFoundException $e) {
                    throw Error::createLocatedError(sprintf('Item "%s" not found.', $args['input']['id']), $info->fieldNodes, $info->path);
                }

                if ($resourceClass !== $this->getObjectClass($item)) {
                    throw Error::createLocatedError(sprintf('Item "%s" did not match expected type "%s".', $args['input']['id'], $resourceMetadata->getShortName()), $info->fieldNodes, $info->path);
                }
            }

            $this->canAccess($this->resourceAccessChecker, $resourceMetadata, $resourceClass, $info, $item, $operationName);

            $inputMetadata = $resourceMetadata->getAttribute('input', ['class' => $resourceClass]);
            if (null === $resourceClass = $inputMetadata['class'] ?? null) {
                return $data;
            }

            if ('delete' === $operationName) {
                if ($item) {
                    $this->dataPersister->remove($item);
                    $data[$wrapFieldName]['id'] = $args['input']['id'];
                } else {
                    $data[$wrapFieldName]['id'] = null;
                }

                return $data;
            }

            $denormalizationContext = null === $item ? ['resource_class' => $resourceClass] : ['resource_class' => $resourceClass, 'object_to_populate' => $item];
            $denormalizationContext += $resourceMetadata->getGraphqlAttribute($operationName, 'denormalization_context', [], true);
            $item = $this->normalizer->denormalize($args['input'], $resourceClass, ItemNormalizer::FORMAT, $denormalizationContext);

            $mutationResolverId = $resourceMetadata->getGraphqlAttribute($operationName, 'mutation');
            if (null !== $mutationResolverId) {
                /** @var MutationResolverInterface $mutationResolver */
                $mutationResolver = $this->mutationResolverLocator->get($mutationResolverId);
                $item = $mutationResolver($item, ['source' => $source, 'args' => $args, 'info' => $info]);
                if (null !== $item && $resourceClass !== $itemClass = $this->getObjectClass($item)) {
                    throw Error::createLocatedError(sprintf('Custom mutation resolver "%s" has to return an item of class %s but returned an item of class %s.', $mutationResolverId, $resourceMetadata->getShortName(), (new \ReflectionClass($itemClass))->getShortName()), $info->fieldNodes, $info->path);
                }
            }

            if (null !== $item) {
                $this->validate($item, $info, $resourceMetadata, $operationName);
                $persistResult = $this->dataPersister->persist($item);

                if (null === $persistResult) {
                    @trigger_error(sprintf('Returning void from %s::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3, an object should always be returned.', DataPersisterInterface::class), E_USER_DEPRECATED);
                }
            }

            return [$wrapFieldName => $this->normalizer->normalize($persistResult ?? $item, ItemNormalizer::FORMAT, $normalizationContext)] + $data;
        };
    }

    /**
     * @param object $item
     *
     * @throws Error
     */
    private function validate($item, ResolveInfo $info, ResourceMetadata $resourceMetadata, string $operationName = null): void
    {
        if (null === $this->validator) {
            return;
        }

        $validationGroups = $resourceMetadata->getGraphqlAttribute($operationName, 'validation_groups', null, true);
        try {
            $this->validator->validate($item, ['groups' => $validationGroups]);
        } catch (ValidationException $e) {
            throw Error::createLocatedError($e->getMessage(), $info->fieldNodes, $info->path);
        }
    }
}
