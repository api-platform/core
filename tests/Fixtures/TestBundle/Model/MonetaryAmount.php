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

class MonetaryAmount
{
    public function __construct(public float $value = 0.0, public string $currency = 'EUR', public float $minValue = 0.0)
    {
    }
}
