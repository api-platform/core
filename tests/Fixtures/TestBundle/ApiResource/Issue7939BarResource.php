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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/issue7939_foos/{fooId}/bars/{id}',
            uriVariables: [
                'fooId' => new Link(fromClass: Issue7939FooResource::class, toProperty: 'foo'),
                'id' => new Link(fromClass: self::class),
            ],
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class Issue7939BarResource
{
    private const PARENTS = ['B' => 'F2'];

    public string $id = '';
    public ?Issue7939FooResource $foo = null;

    public static function parentOf(string $barId): ?string
    {
        return self::PARENTS[$barId] ?? null;
    }

    public static function provide(Operation $operation, array $uriVariables = [])
    {
        $id = (string) ($uriVariables['id'] ?? '');
        $parent = self::parentOf($id);

        if (null === $parent) {
            return null;
        }

        $bar = new self();
        $bar->id = $id;
        $foo = new Issue7939FooResource();
        $foo->id = $parent;
        $bar->foo = $foo;

        return $bar;
    }
}
