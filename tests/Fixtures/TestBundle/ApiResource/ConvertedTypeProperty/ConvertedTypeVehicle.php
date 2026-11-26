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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ConvertedTypeProperty;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[ApiResource(
    operations: [new Get(), new Post(), new Put()],
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'car' => ConvertedTypeCar::class,
    'truck' => ConvertedTypeTruck::class,
])]
abstract class ConvertedTypeVehicle
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    public string $name = '';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): ?self
    {
        return ConvertedTypeVehicleStore::$vehicles[(int) $uriVariables['id']] ?? null;
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $data->id ??= (ConvertedTypeVehicleStore::$vehicles ? max(array_keys(ConvertedTypeVehicleStore::$vehicles)) : 0) + 1;

        return ConvertedTypeVehicleStore::$vehicles[$data->id] = $data;
    }
}
