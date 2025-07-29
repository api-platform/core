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

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedResourceWithRelationRelated;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ORM\Entity]
#[Map(target: MappedResourceWithRelationRelated::class)]
class MappedResourceWithRelationRelatedEntity
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    public string $name;

    public function getId(): ?int
    {
        return $this->id;
    }
}
