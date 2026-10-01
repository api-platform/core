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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract resource exposing its subtypes through a serializer discriminator map.
 * Subtypes are not resources themselves: the discriminator map is the contract declaring them.
 */
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
    ],
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
#[DiscriminatorMap(typeProperty: 'kind', mapping: [
    'fiction' => DiscriminatedFictionBook::class,
    'technical' => DiscriminatedTechnicalBook::class,
])]
abstract class DiscriminatedBook
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    #[Assert\NotBlank]
    public string $title = '';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self|array|null
    {
        if ($operation instanceof GetCollection) {
            return array_values(DiscriminatedBookStore::$books);
        }

        return DiscriminatedBookStore::$books[(int) $uriVariables['id']] ?? null;
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?self
    {
        if ($operation instanceof Delete) {
            unset(DiscriminatedBookStore::$books[(int) $uriVariables['id']]);

            return null;
        }

        if (isset($uriVariables['id'])) {
            $data->id = (int) $uriVariables['id'];
        }

        $data->id ??= (DiscriminatedBookStore::$books ? max(array_keys(DiscriminatedBookStore::$books)) : 0) + 1;

        return DiscriminatedBookStore::$books[$data->id] = $data;
    }
}
