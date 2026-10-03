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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A resource in the middle of the hierarchy: it resolves its own subtypes from the Doctrine map.
 */
#[ApiResource(operations: [new Get(), new Post()])]
#[ORM\Entity]
class DoctrineDiscriminatedCar extends DoctrineDiscriminatedVehicle
{
    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    public int $seats = 4;

    #[ORM\Column(nullable: true)]
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    public ?string $vin = null;
}
