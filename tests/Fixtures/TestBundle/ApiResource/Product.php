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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[Get(
    types: ['https://schema.org/Product'],
    uriTemplate: '/json-stream-products/{code}',
    uriVariables: ['code'],
    provider: [self::class, 'provide'],
    jsonStream: true
)]
#[GetCollection(
    types: ['https://schema.org/Product'],
    uriTemplate: '/json-stream-products',
    provider: [self::class, 'provide'],
    normalizationContext: ['groups' => ['with_aggregate_rating'], 'hydra_prefix' => false]
)]
class Product
{
    #[ApiProperty(identifier: true)]
    public string $code;

    #[ApiProperty(genId: false, iris: ['https://schema.org/aggregateRating'])]
    #[Groups(['with_aggregate_rating'])]
    public AggregateRating $aggregateRating;

    #[ApiProperty(property: 'name', iris: ['https://schema.org/name'])]
    public string $name;

    public static function provide()
    {
        $s = new self();
        $s->code = 'test';
        $s->name = 'foo';
        $s->aggregateRating = new AggregateRating(1.0, 2);

        return $s;
    }
}
