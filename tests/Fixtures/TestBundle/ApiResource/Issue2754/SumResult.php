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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue2754;

// Fields deliberately differ from Sum: the mutation payload exposes them only if it honors output.
class SumResult
{
    public function __construct(public ?int $id = null, public ?int $sum = null)
    {
    }
}
