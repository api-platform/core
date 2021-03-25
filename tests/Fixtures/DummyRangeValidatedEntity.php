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

class DummyRangeValidatedEntity
{
    /**
     * @var int
     *
     * @Assert\Range(min=1)
     */
    public $dummyIntMin;

    /**
     * @var int
     *
     * @Assert\Range(max=10)
     */
    public $dummyIntMax;

    /**
     * @var int
     *
     * @Assert\Range(min=1, max=10)
     */
    public $dummyIntMinMax;

    /**
     * @var float
     *
     * @Assert\Range(min=1.5)
     */
    public $dummyFloatMin;

    /**
     * @var float
     *
     * @Assert\Range(max=10.5)
     */
    public $dummyFloatMax;

    /**
     * @var float
     *
     * @Assert\Range(min=1.5, max=10.5)
     */
    public $dummyFloatMinMax;
}
