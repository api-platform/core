<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\Api\Entrypoint;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes the API entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointNormalizer implements NormalizerInterface
{
    const FORMAT = 'jsonld';

    private $resourceMetadataFactory;
    private $iriConverter;
    private $urlGenerator;
    private $defaultReferenceType;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, IriConverterInterface $iriConverter, UrlGeneratorInterface $urlGenerator, int $defaultReferenceType = UrlGeneratorInterface::ABS_PATH)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->iriConverter = $iriConverter;
        $this->urlGenerator = $urlGenerator;
        $this->defaultReferenceType = $defaultReferenceType;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $entrypoint = [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'Entrypoint'], $this->defaultReferenceType),
            '@id' => $this->urlGenerator->generate('api_entrypoint', [], $this->defaultReferenceType),
            '@type' => 'Entrypoint',
        ];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if (empty($resourceMetadata->getCollectionOperations())) {
                continue;
            }
            try {
                $entrypoint[lcfirst($resourceMetadata->getShortName())] = $this->iriConverter->getIriFromResourceClass($resourceClass, $this->defaultReferenceType);
            } catch (InvalidArgumentException $ex) {
                // Ignore resources without GET operations
            }
        }

        return $entrypoint;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return self::FORMAT === $format && $data instanceof Entrypoint;
    }
}
