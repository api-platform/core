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

namespace ApiPlatform\HttpCache;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\PropertyInfo\Type;

/**
 * Collects cache tags during normalization.
 *
 * @author Urban Suppiger <urban@suppiger.net>
 */
class TagCollector implements TagCollectorInterface
{
    public function collectTagsFromNormalize(mixed $object, string $format = null, array $context = [], string $iri = null): void
    {
        $this->addResourceToContext($context, $iri);
    }

    public function collectTagsFromNormalizeRelation(mixed $object, string $format = null, array $context = [], string $iri = null): void
    {
        $this->addResourceToContext($context, $iri);
    }

    public function collectTagsFromGetAttribute(mixed $object, string $format = null, array $context = [], string $iri = null, string $attribute = null, ApiProperty $propertyMetadata = null, Type $type = null, array $childContext = []): void
    {
    }

    private function addResourceToContext(array $context, ?string $iri): void
    {
        if (isset($context['resources']) && isset($iri)) {
            $context['resources'][$iri] = $iri;
        }
    }
}
