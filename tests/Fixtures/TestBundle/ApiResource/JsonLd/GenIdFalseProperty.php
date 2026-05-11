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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'JsonLdGenIdFalseProperty',
    operations: [
        new Get(
            uriTemplate: '/jsonld_genid_false_properties/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class GenIdFalseProperty
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    #[ApiProperty(genId: false)]
    public GenIdMonetaryAmount $totalPrice;

    public function __construct()
    {
        $this->totalPrice = new GenIdMonetaryAmount(42);
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);

        return $r;
    }
}

final class GenIdMonetaryAmount
{
    public function __construct(public readonly float $value)
    {
    }
}
