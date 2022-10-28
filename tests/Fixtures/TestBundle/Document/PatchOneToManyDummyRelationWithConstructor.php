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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource(
 *     itemOperations={
 *         "get"
 *     }
 * )
 * @ODM\Document
 */
class PatchOneToManyDummyRelationWithConstructor
{
    /**
     * @ApiProperty(writable=false)
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=PatchOneToManyDummy::class, inversedBy="relations")
     */
    protected $related;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
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
