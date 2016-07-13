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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Hypermedia\ContextBuilderInterface;
use ApiPlatform\Core\JsonLd\EntrypointBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointBuilder implements EntrypointBuilderInterface
{
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $iriConverter;
    private $urlGenerator;
    private $contextBuilder;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, IriConverterInterface $iriConverter, UrlGeneratorInterface $urlGenerator, ContextBuilderInterface $contextBuilder)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->iriConverter = $iriConverter;
        $this->urlGenerator = $urlGenerator;
        $this->contextBuilder = $contextBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntrypoint(string $referenceType = UrlGeneratorInterface::ABS_PATH) : array
    {
        $entrypoint = $this->contextBuilder->getBaseContext($referenceType);

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            if (empty($resourceMetadata->getCollectionOperations())) {
                continue;
            }
            try {
                $entrypoint['_links']['ap:'.$resourceMetadata->getShortName()] = ['href' => $this->iriConverter->getIriFromResourceClass($resourceClass)];
            } catch (InvalidArgumentException $ex) {
                // Ignore resources without GET operations
            }
        }

        return $entrypoint;
    }
}
