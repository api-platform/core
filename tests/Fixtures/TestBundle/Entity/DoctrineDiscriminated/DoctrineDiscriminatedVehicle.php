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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Single table inheritance: the subtypes are only declared in the Doctrine discriminator map.
 */
#[ApiResource(operations: [new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()])]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string')]
#[ORM\DiscriminatorMap([
    'car' => DoctrineDiscriminatedCar::class,
    'sportsCar' => DoctrineDiscriminatedSportsCar::class,
    'truck' => DoctrineDiscriminatedTruck::class,
])]
abstract class DoctrineDiscriminatedVehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    public string $name = '';
}
