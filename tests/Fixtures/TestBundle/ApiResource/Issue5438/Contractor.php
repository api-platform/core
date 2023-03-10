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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get(
    shortName: 'Contractor5438',
    uriTemplate: 'contractor_5438/{id}',
    provider: [Contractor::class, 'getContractor'],
)]
class Contractor extends Person
{
    public static function getContractor(Operation $operation, array $uriVariables = []): self
    {
        return new self(
            $uriVariables['id'],
            'a'
        );
    }
}
