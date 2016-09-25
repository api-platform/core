<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hal\Serializer;

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
    const FORMAT = 'jsonhal';

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
        $entrypoint = ['_links' => ['self' => ['href' => $this->urlGenerator->generate('api_entrypoint', [], $this->defaultReferenceType)]]];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if (empty($resourceMetadata->getCollectionOperations())) {
                continue;
            }
            try {
                $entrypoint['_links'][lcfirst($resourceMetadata->getShortName())]['href'] = $this->iriConverter->getIriFromResourceClass($resourceClass, $this->defaultReferenceType);
            } catch (InvalidArgumentException $ex) {
                // Ignore resources without GET operations
            }
        }

        return $entrypoint;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof Entrypoint;
    }
}
