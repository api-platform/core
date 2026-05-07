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
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'JsonApiIncludeGroup',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new Get(
            uriTemplate: '/jsonapi_include_groups/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class IncludeGroup
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $foo;

    public string $bar;

    public string $baz;

    public function __construct(int $id = 1)
    {
        $this->id = $id;
        $this->foo = "Foo #{$id}";
        $this->bar = "Bar #{$id}";
        $this->baz = "Baz #{$id}";
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self((int) ($uriVariables['id'] ?? 1));
    }
}
