<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd;

use Dunglas\ApiBundle\Api\IriConverterInterface;
use Dunglas\ApiBundle\Api\UrlGeneratorInterface;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Metadata\Resource\Factory\CollectionMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Resource\Factory\ItemMetadataFactoryInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointBuilder implements EntrypointBuilderInterface
{
    private $collectionMetadataFactory;
    private $itemMetadataFactory;
    private $iriConverter;
    private $urlGenerator;

    public function __construct(CollectionMetadataFactoryInterface $collectionMetadataFactory, ItemMetadataFactoryInterface $itemMetadataFactory, IriConverterInterface $iriConverter, UrlGeneratorInterface $urlGenerator)
    {
        $this->collectionMetadataFactory = $collectionMetadataFactory;
        $this->itemMetadataFactory = $itemMetadataFactory;
        $this->iriConverter = $iriConverter;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntrypoint(string $referenceType = UrlGeneratorInterface::ABS_PATH) : array
    {
        $entrypoint = [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'Entrypoint'], $referenceType),
            '@id' => $this->urlGenerator->generate('api_jsonld_entrypoint', [], $referenceType),
            '@type' => 'Entrypoint',
        ];

        foreach ($this->collectionMetadataFactory->create() as $resourceClass) {
            $itemMetadata = $this->itemMetadataFactory->create($resourceClass);

            if (empty($itemMetadata->getCollectionOperations())) {
                continue;
            }
            try {
                $entrypoint[lcfirst($itemMetadata->getShortName())] = $this->iriConverter->getIriFromResourceClass($resourceClass);
            } catch (InvalidArgumentException $ex) {
                // Ignore resources without GET operations
            }
        }

        return $entrypoint;
    }
}
