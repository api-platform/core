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

use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Identifier denormalizer.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class IdentifierDenormalizer implements IdentifierDenormalizerInterface
{
    private $propertyMetadataFactory;
    private $identifierDenormalizers;

    /**
     * @param iterable<DenormalizerInterface> $identifierDenormalizers
     */
    public function __construct(PropertyMetadataFactoryInterface $propertyMetadataFactory, iterable $identifierDenormalizers)
    {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->identifierDenormalizers = $identifierDenormalizers;
    }

    /*
     * {@inheritdoc}
     */
    public function denormalize($identifiers, $class, ?string $format = null, array $context = []): array
    {
        foreach ($identifiers as $identifier => $value) {
            if (null === $type = $this->getIdentifierType($class, $identifier)) {
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
