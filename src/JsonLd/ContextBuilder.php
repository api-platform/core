<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\JsonLd;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextBuilder implements ContextBuilderInterface
{
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $urlGenerator;

    /**
     * @var NameConverterInterface
     */
    private $nameConverter;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, UrlGeneratorInterface $urlGenerator, NameConverterInterface $nameConverter = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->urlGenerator = $urlGenerator;
        $this->nameConverter = $nameConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseContext(int $referenceType = UrlGeneratorInterface::ABS_URL) : array
    {
        return [
            '@vocab' => $this->urlGenerator->generate('api_hydra_vocab', [], UrlGeneratorInterface::ABS_URL).'#',
            'hydra' => self::HYDRA_NS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntrypointContext(int $referenceType = UrlGeneratorInterface::ABS_PATH) : array
    {
        $context = $this->getBaseContext($referenceType);

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $resourceName = lcfirst($resourceMetadata->getShortName());

            $context[$resourceName] = [
                '@id' => 'Entrypoint/'.$resourceName,
                '@type' => '@id',
            ];
        }

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContext(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH) : array
    {
        $context = $this->getBaseContext($referenceType, $referenceType);
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $prefixedShortName = sprintf('#%s', $resourceMetadata->getShortName());

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            if ($propertyMetadata->isIdentifier() && !$propertyMetadata->isWritable()) {
                continue;
            }

            $convertedName = $this->nameConverter ? $this->nameConverter->normalize($propertyName) : $propertyName;

            if (!$id = $propertyMetadata->getIri()) {
                $id = sprintf('%s/%s', $prefixedShortName, $convertedName);
            }

            if (!$propertyMetadata->isReadableLink()) {
                $context[$convertedName] = [
                    '@id' => $id,
                    '@type' => '@id',
                ];
            } else {
                $context[$convertedName] = $id;
            }
        }

        return $context;
    }

    public function getResourceContextUri(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH) : string
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        return $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $resourceMetadata->getShortName()]);
    }
}
