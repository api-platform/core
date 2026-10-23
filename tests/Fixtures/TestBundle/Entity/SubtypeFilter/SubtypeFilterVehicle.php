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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\SubtypeFilter;

use ApiPlatform\Doctrine\Orm\Filter\ComparisonFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrFilter;
use ApiPlatform\Doctrine\Orm\Filter\SubtypeFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            paginationEnabled: false,
            parameters: [
                'seats' => new QueryParameter(
                    filter: new SubtypeFilter(SubtypeFilterCar::class, new ExactFilter()),
                    property: 'seats',
                ),
                'seatsOrOtherType' => new QueryParameter(
                    filter: new SubtypeFilter(SubtypeFilterCar::class, new ExactFilter(), strict: false),
                    property: 'seats',
                ),
                'horsepower' => new QueryParameter(
                    filter: new SubtypeFilter(SubtypeFilterMotorVehicle::class, new ComparisonFilter(new ExactFilter())),
                    property: 'horsepower',
                ),
                'cargoVolume' => new QueryParameter(
                    filter: new SubtypeFilter(SubtypeFilterCargoInterface::class, new ExactFilter()),
                    property: 'cargoVolume',
                ),
                'name' => new QueryParameter(filter: new ExactFilter()),
                'nameOr' => new QueryParameter(filter: new OrFilter(new ExactFilter()), property: 'name'),
                'gearsOr' => new QueryParameter(
                    filter: new OrFilter(new SubtypeFilter(SubtypeFilterBicycle::class, new ExactFilter(), strict: false)),
                    property: 'gears',
                ),
                'unknownSubtype' => new QueryParameter(
                    filter: new SubtypeFilter(\stdClass::class, new ExactFilter()),
                    property: 'name',
                ),
            ],
        ),
    ],
)]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'car' => SubtypeFilterCar::class,
    'sportsCar' => SubtypeFilterSportsCar::class,
    'truck' => SubtypeFilterTruck::class,
    'bicycle' => SubtypeFilterBicycle::class,
])]
abstract class SubtypeFilterVehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public string $name = '';
}
