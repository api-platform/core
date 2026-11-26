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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedTypeIri;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[ApiResource(
    operations: [new Get(), new Post()],
    types: ['https://schema.org/Vehicle'],
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
#[DiscriminatorMap(typeProperty: 'kind', mapping: [
    'car' => SharedTypeCar::class,
    'truck' => SharedTypeTruck::class,
])]
abstract class SharedTypeVehicle
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    public string $name = '';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): ?self
    {
        return SharedTypeVehicleStore::$vehicles[(int) $uriVariables['id']] ?? null;
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $data->id ??= (SharedTypeVehicleStore::$vehicles ? max(array_keys(SharedTypeVehicleStore::$vehicles)) : 0) + 1;

        return SharedTypeVehicleStore::$vehicles[$data->id] = $data;
    }
}
