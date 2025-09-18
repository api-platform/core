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

namespace ApiPlatform\Tests\Fixtures\TestBundle\HttpCache;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Serializer\TagCollectorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;

/**
 * Collects cache tags during normalization.
 *
 * @author Urban Suppiger <urban@suppiger.net>
 */
class TagCollectorCustom implements TagCollectorInterface
{
    public const IRI_RELATION_DELIMITER = '#';

    public function __construct(protected IriConverterInterface $iriConverter)
    {
    }

    public function collect(array $context = []): void
    {
        if (!isset($context['resources'])) {
            return;
        }

        $iri = $context['iri'] ?? null;
        $object = $context['object'] ?? null;

        // Example on using known objects to shorten/simplify the cache tag (e.g. using ID only or using shorter identifiers)
        if ($object && $object instanceof RelationEmbedder) {
            $iri = '/RE/'.$object->id;
        }

        // manually generate IRI, if object is known but IRI is not populated
        if (!$iri && $object) {
            $iri = $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_PATH, $context['operation'] ?? null, $context);
        }

        if (!$iri) {
            return;
        }

        if (isset($context['property_metadata'])) {
            $this->addCacheTagsForRelation($context, $iri, $context['property_metadata']);

            return;
        }

        // Example on how to not include "link-only" resources
        if ($this->isLinkOnly($context)) {
            return;
        }

        $this->addCacheTagForResource($context, $iri);
    }

    private function addCacheTagForResource(array &$context, string $iri): void
    {
        $context['resources'][$iri] = $iri;
    }

    private function addCacheTagsForRelation(array $context, string $iri, ApiProperty $propertyMetadata): void
    {
        // Example on how extra properties could be used to fine-control cache tag behavior for a specific ApiProperty
        if (isset($propertyMetadata->getExtraProperties()['cacheDependencies'])) {
            foreach ($propertyMetadata->getExtraProperties()['cacheDependencies'] as $dependency) {
                $cacheTag = $iri.self::IRI_RELATION_DELIMITER.$dependency;
                $context['resources'][$cacheTag] = $cacheTag;
            }

            return;
        }

        $cacheTag = $iri.self::IRI_RELATION_DELIMITER.$context['api_attribute'];
        $context['resources'][$cacheTag] = $cacheTag;
    }

    /**
     * Returns true, if a resource was normalized into a link only
     * Returns false, if a resource was normalized into a fully embedded resource.
     */
    private function isLinkOnly(array $context): bool
    {
        $format = $context['format'] ?? null;
        $data = $context['data'] ?? null;

        // resource was normalized into JSONAPI link format
        if ('jsonapi' === $format && isset($data['data']) && \is_array($data['data']) && array_keys($data['data']) === ['type', 'id']) {
            return true;
        }

        // resource was normalized into a string IRI only
        if (\in_array($format, ['jsonld', 'jsonhal'], true) && \is_string($data)) {
            return true;
        }

        return false;
    }
}
