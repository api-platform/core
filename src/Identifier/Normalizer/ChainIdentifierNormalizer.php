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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Identifier normalizer.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ChainIdentifierNormalizer implements DenormalizerInterface
{
    const HAS_IDENTIFIER_NORMALIZER = 'has_normalized_identifier';

    private $propertyMetadataFactory;
    private $identifiersExtractor;
    private $identifierNormalizers;
    private $compositeIdentifierParser;

    public function __construct(IdentifiersExtractorInterface $identifiersExtractor, PropertyMetadataFactoryInterface $propertyMetadataFactory, $identifierNormalizers)
    {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->identifierNormalizers = $identifierNormalizers;
        $this->compositeIdentifierParser = new CompositeIdentifierParser();
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidIdentifierException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $keys = $this->identifiersExtractor->getIdentifiersFromResourceClass($class);

        if (\count($keys) > 1) {
            $identifiers = $this->compositeIdentifierParser->parse($data);
        } else {
            $identifiers = [$keys[0] => $data];
        }

        // Normalize every identifier (DateTime, UUID etc.)
        foreach ($keys as $key) {
            foreach ($this->identifierNormalizers as $normalizer) {
                if (!isset($identifiers[$key])) {
                    throw new InvalidIdentifierException(sprintf('Invalid identifier "%s", "%s" was not found.', $key, $key));
                }

                $metadata = $this->getIdentifierMetadata($class, $key);

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

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return true;
    }

    private function getIdentifierMetadata($class, $propertyName)
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($class, $propertyName);
        $type = $propertyMetadata->getType();

        return $type && Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType() ? $type->getClassName() : null;
    }
}
