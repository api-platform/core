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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(
    operations: [
        new Get(),
    ]
)]
#[ODM\Document]
class PatchOneToManyDummyRelationWithConstructor
{
    #[ApiProperty(writable: false)]
    #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
    public $id;

    #[ODM\ReferenceOne(targetDocument: PatchOneToManyDummy::class, inversedBy: 'relations')]
    protected $related;

    public function __construct(#[ODM\Field(type: 'string')] public $name)
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRelated(): ?PatchOneToManyDummy
    {
        return $this->related;
    }

    public function setRelated(?PatchOneToManyDummy $related): void
    {
        $this->related = $related;
    }
}
