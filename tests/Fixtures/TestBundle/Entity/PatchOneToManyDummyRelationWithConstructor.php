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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(),
    ]
)]
#[ORM\Entity]
class PatchOneToManyDummyRelationWithConstructor
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PatchOneToManyDummy::class, inversedBy: 'relations')]
    protected ?PatchOneToManyDummy $related = null;

    public function __construct(#[ORM\Column] public string $name)
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
