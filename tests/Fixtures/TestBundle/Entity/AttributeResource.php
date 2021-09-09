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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

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
#[ApiResource('/dummy/{dummyId}/attribute_resources/{identifier}.{_format}', uriVariables: ['dummyId' => ['class' => Dummy::class, 'identifiers' => ['id']], 'identifier' => ['class' => AttributeResource::class, 'identifiers' => ['identifier']]], inputFormats: ['json' => ['application/merge-patch+json']])]
#[Get]
#[Patch]
final class AttributeResource
{
    #[ApiProperty(identifier: true)]
    private $identifier;

    /**
     * @var ?Dummy
     */
    public $dummy = null;

    /**
     * @var string
     */
    public $name;

    public function __construct(int $identifier, string $name)
    {
        $this->identifier = $identifier;
        $this->name = $name;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getName()
    {
        return $this->name;
    }
}
