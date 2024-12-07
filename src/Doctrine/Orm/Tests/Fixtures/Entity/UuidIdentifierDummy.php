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

namespace ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * UUID identifier dummy.
 */
#[ApiResource]
#[ApiFilter(SearchFilter::class, properties: ['id' => 'exact', 'uuidField' => 'exact', 'relatedUuidIdentifierDummy' => 'exact'])]
#[ORM\Entity]
class UuidIdentifierDummy
{
    #[ORM\Column(type: 'uuid')]
    #[ORM\Id]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $uuidField;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: RelatedUuidIdentifierDummy::class)]
    private RelatedUuidIdentifierDummy $relatedUuidIdentifierDummy;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
    }

    public function getUuidField(): Uuid
    {
        return $this->uuidField;
    }

    public function setUuidField(Uuid $uuidField): void
    {
        $this->uuidField = $uuidField;
    }

    public function getRelatedUuidIdentifierDummy(): RelatedUuidIdentifierDummy
    {
        return $this->relatedUuidIdentifierDummy;
    }

    public function setRelatedUuidIdentifierDummy(RelatedUuidIdentifierDummy $relatedUuidIdentifierDummy): void
    {
        $this->relatedUuidIdentifierDummy = $relatedUuidIdentifierDummy;
    }
}
