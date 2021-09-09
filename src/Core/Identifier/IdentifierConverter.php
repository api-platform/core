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

namespace ApiPlatform\Core\Identifier;

use ApiPlatform\Api\UriVariablesConverterInterface;
use ApiPlatform\Api\UriVariableTransformerInterface;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface as LegacyPropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Identifier converter that chains identifier denormalizers.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class IdentifierConverter implements ContextAwareIdentifierConverterInterface
{
    /**
     * @var LegacyPropertyMetadataFactoryInterface|PropertyMetadataFactoryInterface
     */
    private $propertyMetadataFactory;
    private $identifiersExtractor;
    private $identifierDenormalizers;
    private $resourceMetadataFactory;

    /**
     * TODO: rename identifierDenormalizers to identifierTransformers in 3.0 and change their interfaces to a IdentifierTransformerInterface.
     *
     * @param iterable<DenormalizerInterface> $identifierDenormalizers
     */
    public function __construct(IdentifiersExtractorInterface $identifiersExtractor, $propertyMetadataFactory, iterable $identifierDenormalizers, ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->identifierDenormalizers = $identifierDenormalizers;
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        trigger_deprecation('api-platform/core', '2.7', sprintf('The class "%s" is deprecated, implement the new "%s" interface instead. Note that identifier denormalizers are now implementing the new "%s" interface instead of "%s".', __CLASS__, UriVariablesConverterInterface::class, UriVariableTransformerInterface::class, DenormalizerInterface::class));
    }

    /**
     * {@inheritdoc}
     */
    public function convert($data, string $class, array $context = []): array
    {
        if (!\is_array($data)) {
            @trigger_error(sprintf('Not using an array as the first argument of "%s->convert" is deprecated since API Platform 2.6 and will not be possible anymore in API Platform 3', self::class), \E_USER_DEPRECATED);
            $data = ['id' => $data];
        }

        $identifiers = $data;

        foreach ($data as $parameter => $value) {
            if (null === $type = $this->getIdentifierType($context['identifiers'][$parameter][0] ?? $class, $context['identifiers'][$parameter][1] ?? $parameter)) {
                continue;
            }

            /* @var DenormalizerInterface[] */
            foreach ($this->identifierDenormalizers as $identifierTransformer) {
                if (!$identifierTransformer->supportsDenormalization($value, $type)) {
                    continue;
                }

                try {
                    $identifiers[$parameter] = $identifierTransformer->denormalize($value, $type);
                    break;
                } catch (InvalidIdentifierException $e) {
                    throw new InvalidIdentifierException(sprintf('Identifier "%s" could not be denormalized.', $parameter), $e->getCode(), $e);
                }
            }
        }

        return $identifiers;
    }

    private function getIdentifierType(string $resourceClass, string $property): ?string
    {
        /** @var ApiProperty|PropertyMetadata */
        $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);
        // TODO: 3.0 support multiple types, default value of types will be [] instead of null
        $type = $propertyMetadata instanceof PropertyMetadata ? $propertyMetadata->getType() : ($propertyMetadata->getBuiltinTypes()[0] ?? null);

        if (!$type) {
            return null;
        }

        return Type::BUILTIN_TYPE_OBJECT === ($builtinType = $type->getBuiltinType()) ? $type->getClassName() : $builtinType;
    }
}
