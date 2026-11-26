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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\MappedDiscriminatedVehicle;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DoctrineDiscriminated\DoctrineDiscriminatedVehicle;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[ApiResource(
    shortName: 'MappedVehicle',
    operations: [new Get(), new GetCollection(), new Post()],
    stateOptions: new Options(entityClass: DoctrineDiscriminatedVehicle::class),
)]
#[DiscriminatorMap(typeProperty: 'kind', mapping: ['car' => MappedCar::class, 'truck' => MappedTruck::class])]
abstract class MappedVehicle
{
    public ?int $id = null;
    public string $name = '';
}
