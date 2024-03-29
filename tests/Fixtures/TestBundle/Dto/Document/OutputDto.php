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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document;

use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as DocumentRelatedDummy;

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
     * @var DocumentRelatedDummy[]
     */
    public $relatedDummies = [];
}
