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

namespace ApiPlatform\Metadata\Extractor;

use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Extracts a dynamic resource (used by GraphQL for nested resources).
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class DynamicResourceExtractor implements DynamicResourceExtractorInterface
{
    private array $dynamicResources = [];

    /**
     * {@inheritdoc}
     */
    public function getResources(): array
    {
        return $this->dynamicResources;
    }

    public function addResource(string $resourceClass, array $config = []): string
    {
        $dynamicResourceName = $this->getDynamicResourceName($resourceClass);

        $this->dynamicResources[$dynamicResourceName] = [
            array_merge(['class' => $resourceClass], $config),
        ];

        return $dynamicResourceName;
    }

    private function getDynamicResourceName(string $resourceClass): string
    {
        return ResourceMetadataCollection::DYNAMIC_RESOURCE_CLASS_PREFIX.$resourceClass;
    }
}
