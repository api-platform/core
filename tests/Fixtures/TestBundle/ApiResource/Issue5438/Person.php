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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5438;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[GetCollection(
    shortName: 'People5438',
    uriTemplate: 'people_5438',
    provider: [Person::class, 'getData']
)]
abstract class Person
{
    public function __construct(public readonly ?int $id = null, public readonly ?string $name = null)
    {
    }

    public static function getData(Operation $operation, array $uriVariables = []): iterable
    {
        return [
            new Contractor(
                1,
                'a'
            ),
            new Employee(
                2,
                'b'
            ),
        ];
    }
}
