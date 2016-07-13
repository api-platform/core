<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hal;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Hypermedia\ContextBuilderInterface;
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
    private $docUri;

    /**
     * @var NameConverterInterface
     */
    private $nameConverter;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, UrlGeneratorInterface $urlGenerator, string $docUri = '', NameConverterInterface $nameConverter = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->urlGenerator = $urlGenerator;
        $this->nameConverter = $nameConverter;
        $this->docUri = $docUri;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseContext(int $referenceType = UrlGeneratorInterface::ABS_URL, string $linkUrl = '/') : array
    {
        return [
            '_links' => ['self' => ['href' => $referenceType ? $this->urlGenerator->generate('api_hal_entrypoint') : $linkUrl],
                         'curies' => [
                             ['name' => 'ap',
                              'href' => $this->urlGenerator->generate('api_hal_entrypoint').$this->docUri.'#section-{rel}',
                              'templated' => true,
                             ],
                         ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntrypointContext(int $referenceType = UrlGeneratorInterface::ABS_PATH) : array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContext(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH) : array
    {
        return [];
    }

    public function getResourceContextUri(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH) : string
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        return $this->urlGenerator->generate('api_jsonhal_context', ['shortName' => $resourceMetadata->getShortName()]);
    }
}
