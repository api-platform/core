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
use ApiPlatform\Core\Metadata\Property\Factory\CollectionMetadataFactoryInterface as PropertyCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\ItemMetadataFactoryInterface as PropertyItemMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\CollectionMetadataFactoryInterface as ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ItemMetadataFactoryInterface as ResourceItemMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextBuilder implements ContextBuilderInterface
{
    private $resourceCollectionMetadataFactory;
    private $resourceItemMetadataFactory;
    private $propertyCollectionMetadataFactory;
    private $propertyItemMetadataFactory;
    private $urlGenerator;

    /**
     * @var NameConverterInterface
     */
    private $nameConverter;

    public function __construct(ResourceCollectionMetadataFactoryInterface $resourceCollectionMetadataFactory, ResourceItemMetadataFactoryInterface $resourceItemMetadataFactory, PropertyCollectionMetadataFactoryInterface $propertyCollectionMetadataFactory, PropertyItemMetadataFactoryInterface $propertyItemMetadataFactory, UrlGeneratorInterface $urlGenerator, NameConverterInterface $nameConverter = null)
    {
        $this->resourceCollectionMetadataFactory = $resourceCollectionMetadataFactory;
        $this->resourceItemMetadataFactory = $resourceItemMetadataFactory;
        $this->propertyCollectionMetadataFactory = $propertyCollectionMetadataFactory;
        $this->propertyItemMetadataFactory = $propertyItemMetadataFactory;
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

        foreach ($this->resourceCollectionMetadataFactory->create() as $resourceClass) {
            $itemMetadata = $this->resourceItemMetadataFactory->create($resourceClass);

            $resourceName = lcfirst($itemMetadata->getShortName());

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
        $itemMetadata = $this->resourceItemMetadataFactory->create($resourceClass);
        $prefixedShortName = sprintf('#%s', $itemMetadata->getShortName());

        foreach ($this->propertyCollectionMetadataFactory->create($resourceClass) as $propertyName) {
            $propertyItemMetadata = $this->propertyItemMetadataFactory->create($resourceClass, $propertyName);

            if ($propertyItemMetadata->isIdentifier() && !$propertyItemMetadata->isWritable()) {
                continue;
            }

            $convertedName = $this->nameConverter ? $this->nameConverter->normalize($propertyName) : $propertyName;

            if (!$id = $propertyItemMetadata->getIri()) {
                $id = sprintf('%s/%s', $prefixedShortName, $convertedName);
            }

            if (!$propertyItemMetadata->isReadableLink()) {
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
        $itemMetadata = $this->resourceItemMetadataFactory->create($resourceClass);

        return $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $itemMetadata->getShortName()]);
    }
}
