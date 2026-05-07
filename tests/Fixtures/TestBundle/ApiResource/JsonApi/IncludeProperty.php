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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'JsonApiIncludeProperty',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_include_properties',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonapi_include_properties/{id}',
            uriVariables: ['id'],
            filters: ['dummy_property.property'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class IncludeProperty
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $foo;

    public string $bar;

    public string $baz;

    public ?IncludeGroup $group = null;

    /** @var IncludeGroup[] */
    public array $groups = [];

    public function __construct(int $id = 1)
    {
        $this->id = $id;
        $this->foo = "Foo #{$id}";
        $this->bar = "Bar #{$id}";
        $this->baz = "Baz #{$id}";
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self((int) ($uriVariables['id'] ?? 1));
        $r->group = new IncludeGroup(1);
        $r->groups = [new IncludeGroup(2), new IncludeGroup(3), new IncludeGroup(4)];

        return $r;
    }

    public static function provideCollection(): array
    {
        return [self::provide(new Get(), ['id' => 1], [])];
    }
}
