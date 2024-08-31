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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\DummyNonResource;

#[Get('/dummy-with-array-of-objects/{id}', filters: ['my_dummy.property'], provider: [DummyWithArrayOfNotResourceObjects::class, 'getData'])]
class DummyWithArrayOfNotResourceObjects
{
    public function __construct(
        public readonly int $id,
        #[ApiProperty(genId: false)]
        public readonly DummyNonResource $notResourceObject,
        /** @var array<DummyNonResource> */
        public readonly array $arrayOfNotResourceObjects,
    ) {
    }

    public static function getData(Operation $operation, array $uriVariables = []): self
    {
        return new self(
            $uriVariables['id'],
            new DummyNonResource('foo', 'foo'),
            [
                new DummyNonResource('bar', 'bar'),
                new DummyNonResource('baz', 'baz'),
            ]
        );
    }
}
