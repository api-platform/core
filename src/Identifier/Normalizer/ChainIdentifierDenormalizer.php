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

namespace ApiPlatform\Core\Identifier\Normalizer;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Identifier\CompositeIdentifierParser;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Identifier normalizer.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ChainIdentifierDenormalizer
{
    const HAS_IDENTIFIER_DENORMALIZER = 'has_identifier_denormalizer';

    private $propertyMetadataFactory;
    private $identifiersExtractor;
    private $identifierDenormalizers;

    public function __construct(IdentifiersExtractorInterface $identifiersExtractor, PropertyMetadataFactoryInterface $propertyMetadataFactory, $identifierDenormalizers)
    {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->identifierDenormalizers = $identifierDenormalizers;
    }

    /**
     * @throws InvalidIdentifierException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $keys = $this->identifiersExtractor->getIdentifiersFromResourceClass($class);

        if (!$keys) {
            throw new InvalidIdentifierException(sprintf('Resource "%s" has no identifiers.', $class));
        }

        if (\count($keys) > 1) {
            $identifiers = CompositeIdentifierParser::parse($data);
        } else {
            $identifiers = [$keys[0] => $data];
        }

        // Normalize every identifier (DateTime, UUID etc.)
        foreach ($keys as $key) {
            if (!isset($identifiers[$key])) {
                throw new InvalidIdentifierException(sprintf('Invalid identifier "%1$s", "%1$s" was not found.', $key));
            }

            $metadata = $this->getIdentifierMetadata($class, $key);
            foreach ($this->identifierDenormalizers as $normalizer) {
                if (!$normalizer->supportsDenormalization($identifiers[$key], $metadata)) {
                    continue;
                }

                try {
                    $identifiers[$key] = $normalizer->denormalize($identifiers[$key], $metadata);
                } catch (InvalidIdentifierException $e) {
                    throw new InvalidIdentifierException(sprintf('Identifier "%s" could not be denormalized.', $key), $e->getCode(), $e);
                }
            }
        }

        return $identifiers;
    }

    private function getIdentifierMetadata($class, $propertyName)
    {
        if (!$type = $this->propertyMetadataFactory->create($class, $propertyName)->getType()) {
            return null;
        }

        return Type::BUILTIN_TYPE_OBJECT === ($builtInType = $type->getBuiltinType()) ? $type->getClassName() : $builtInType;
    }
}
