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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'JsonApiFilteringProperty',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    paginationEnabled: false,
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_filtering_properties',
            filters: ['dummy_property.property'],
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class FilteringProperty
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $foo;

    public string $bar;

    public string $group;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->foo = "Foo #{$id}";
        $this->bar = "Bar #{$id}";
        $this->group = "Group #{$id}";
    }

    public static function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return [new self(1), new self(2)];
    }
}
