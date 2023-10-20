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
use ApiPlatform\Serializer\TagCollectorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use Symfony\Component\PropertyInfo\Type;

/**
 * Collects cache tags during normalization.
 *
 * @author Urban Suppiger <urban@suppiger.net>
 */
class TagCollectorDefault implements TagCollectorInterface
{
    public function collect(mixed $object = null, string $format = null, array $context = [], string $iri = null, mixed $data = null, string $attribute = null, ApiProperty $propertyMetadata = null, Type $type = null): void
    {
        if (!$attribute) {
            $this->addResourceToContext($context, $iri);
        }
    }

    private function addResourceToContext(array $context, ?string $iri): void
    {
        if (isset($context['resources']) && isset($iri)) {
            $context['resources'][$iri] = $iri;
        }
    }
}
