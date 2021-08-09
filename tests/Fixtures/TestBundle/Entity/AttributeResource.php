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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;

#[ApiResource(normalizationContext: ['skip_null_values' => true])]
#[Get]
#[Put]
#[Delete]
#[ApiResource('/dummy/{dummyId}/attribute_resources/{identifier}.{_format}', identifiers: ['dummyId' => [Dummy::class, 'id'], 'identifier' => [AttributeResource::class, 'identifier']], inputFormats: ['json' => ['application/merge-patch+json']])]
#[Get]
#[Patch]
final class AttributeResource
{
    #[ApiProperty(identifier: true)]
    private int $identifier;

    public ?Dummy $dummy = null;

    public function __construct(int $identifier, public string $name)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }
}
