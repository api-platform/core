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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [new Get(), new Post()])]
#[ODM\Document]
class DoctrineDiscriminatedCar extends DoctrineDiscriminatedVehicle
{
    #[ODM\Field(type: 'int')]
    #[Assert\Positive]
    public int $seats = 4;

    #[ODM\Field(nullable: true)]
    #[ApiProperty(security: "is_granted('ROLE_ADMIN')")]
    public ?string $vin = null;
}
