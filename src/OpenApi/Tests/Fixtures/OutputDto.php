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

namespace ApiPlatform\OpenApi\Tests\Fixtures;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class OutputDto
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var float
     */
    public $baz;

    /**
     * @var string
     */
    public $bat;

    /**
     * @var RelatedDummy[]
     */
    public $relatedDummies = [];
}
