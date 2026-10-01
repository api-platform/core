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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\DoctrineDiscriminated;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Single collection inheritance: the subtypes are only declared in the Doctrine discriminator map.
 */
#[ApiResource(operations: [new Get(), new GetCollection(), new Post(), new Put(), new Patch(), new Delete()])]
#[ODM\Document]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\DiscriminatorField('kind')]
#[ODM\DiscriminatorMap([
    'car' => DoctrineDiscriminatedCar::class,
    'sportsCar' => DoctrineDiscriminatedSportsCar::class,
    'truck' => DoctrineDiscriminatedTruck::class,
])]
abstract class DoctrineDiscriminatedVehicle
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public ?int $id = null;

    #[ODM\Field]
    #[Assert\NotBlank]
    public string $name = '';
}
