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

namespace ApiPlatform\Core\Tests\Fixtures;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;

/**
 * @ApiFilter(OrderFilter::class, arguments={"orderParameterName"="positionOrder"}, properties={"position"})
 */
class DummyEntityFilterAnnotated
{
    public $position;

    /**
     * @ApiFilter(OrderFilter::class)
     */
    public $priority;

    /**
     * @ApiFilter(OrderFilter::class, strategy="ASC")
     */
    public $number;
}
