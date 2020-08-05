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

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Identifier converter that chains identifier denormalizers.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class IdentifierConverter implements NormalizeIdentifierConverterInterface
{
    private $propertyMetadataFactory;
    private $identifiersExtractor;
    private $identifierDenormalizers;
    private $resourceMetadataFactory;

    /**
     * @param iterable<DenormalizerInterface> $identifierDenormalizers
     */
    public function __construct(IdentifiersExtractorInterface $identifiersExtractor, PropertyMetadataFactoryInterface $propertyMetadataFactory, iterable $identifierDenormalizers, ResourceMetadataFactoryInterface $resourceMetadataFactory = null)
    {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->identifierDenormalizers = $identifierDenormalizers;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(string $data, string $class, array $context = []): array
    {
        if (null !== $this->resourceMetadataFactory) {
            $resourceMetadata = $this->resourceMetadataFactory->create($class);
            $class = $resourceMetadata->getOperationAttribute($context, 'output', ['class' => $class], true)['class'];
        }

        $keys = $this->identifiersExtractor->getIdentifiersFromResourceClass($class);

        if (($numIdentifiers = \count($keys)) > 1) {
            // todo put this in normalizer
            $identifiers = CompositeIdentifierParser::parse($data);
        } elseif (0 === $numIdentifiers) {
            throw new InvalidIdentifierException(sprintf('Resource "%s" has no identifiers.', $class));
        } else {
            $identifiers = [$keys[0] => $data];
        }

        return $this->denormalize($identifiers, $class);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($identifiers, $class, $format = null, array $context = []): array
    {
        // Normalize every identifier (DateTime, UUID etc.)
        foreach ($identifiers as $identifier => $value) {
            if (null === $type = $this->getIdentifierType($class, $identifier)) {
                if (preg_match_all(CompositeIdentifierParser::COMPOSITE_IDENTIFIER_REGEXP, $value)) {
                    return CompositeIdentifierParser::parse($value);
                }
                continue;
            }

            foreach ($this->identifierDenormalizers as $identifierDenormalizer) {
                if (!$identifierDenormalizer->supportsDenormalization($value, $type)) {
                    continue;
                }

                try {
                    $identifiers[$identifier] = $identifierDenormalizer->denormalize($value, $type);
                } catch (InvalidIdentifierException $e) {
                    throw new InvalidIdentifierException(sprintf('Identifier "%s" could not be denormalized.', $identifier), $e->getCode(), $e);
                }
            }
        }

        return $identifiers;
    }

    private function getIdentifierType(string $resourceClass, string $property): ?string
    {
        if (!$type = $this->propertyMetadataFactory->create($resourceClass, $property)->getType()) {
            return null;
        }

        return Type::BUILTIN_TYPE_OBJECT === ($builtinType = $type->getBuiltinType()) ? $type->getClassName() : $builtinType;
    }
}
