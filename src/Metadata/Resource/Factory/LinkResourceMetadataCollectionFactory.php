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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Prepares graphql links.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class LinkResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $linkFactory;
    private $decorated;

    public function __construct(LinkFactoryInterface $linkFactory, ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->linkFactory = $linkFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            $graphQlOperations = [];
            foreach ($resource->getGraphQlOperations() ?? [] as $graphQlOperation) {
                $relationLinks = $this->linkFactory->createLinksFromRelations($graphQlOperation);
                $attributeLinks = $this->linkFactory->createLinksFromAttributes($graphQlOperation);
                $links = [];
                foreach ($attributeLinks as $link) {
                    $links[] = $this->linkFactory->completeLink($link);
                }
                $links = $this->mergeLinks($relationLinks, $links);

                $graphQlOperations[$graphQlOperation->getName()] = $graphQlOperation->withLinks($links);
            }

            $resource = $resource->withGraphQlOperations($graphQlOperations);
            $resourceMetadataCollection[$i] = $resource;
        }

        return $resourceMetadataCollection;
    }

    /**
     * @param Link[] $links
     * @param Link[] $toMergeLinks
     *
     * @return Link[]
     */
    private function mergeLinks(array $links, array $toMergeLinks): array
    {
        $classLinks = [];
        foreach ($links as $link) {
            $classLinks[$link->getToClass()] = $link;
        }

        foreach ($toMergeLinks as $link) {
            if (isset($classLinks[$link->getToClass()])) {
                $classLinks[$link->getToClass()] = $classLinks[$link->getToClass()]->withLink($link);

                continue;
            }
            $classLinks[$link->getToClass()] = $link;
        }

        return array_values($classLinks);
    }
}
