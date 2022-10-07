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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Model;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get('/genids/{id}', provider: [GenId::class, 'getData'])]
class GenId
{
    #[ApiProperty(genId: false)]
    public MonetaryAmount $totalPrice;

    public function __construct(public int $id)
    {
        $this->totalPrice = new MonetaryAmount(1000.01);
    }

    public static function getData(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self($uriVariables['id']);
    }
}
