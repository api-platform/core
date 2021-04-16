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

use Symfony\Component\Validator\Constraints as Assert;

class DummyNumericValidatedEntity
{
    /**
     * @var int
     *
     * @Assert\GreaterThan(value=10)
     */
    public $greaterThanMe;

    /**
     * @var float
     *
     * @Assert\GreaterThanOrEqual(value=10.99)
     */
    public $greaterThanOrEqualToMe;

    /**
     * @var int
     *
     * @Assert\LessThan(value=99)
     */
    public $lessThanMe;

    /**
     * @var float
     *
     * @Assert\LessThanOrEqual(value=99.33)
     */
    public $lessThanOrEqualToMe;

    /**
     * @var int
     *
     * @Assert\Positive
     */
    public $positive;

    /**
     * @var int
     *
     * @Assert\PositiveOrZero
     */
    public $positiveOrZero;

    /**
     * @var int
     *
     * @Assert\Negative
     */
    public $negative;

    /**
     * @var int
     *
     * @Assert\NegativeOrZero
     */
    public $negativeOrZero;
}
