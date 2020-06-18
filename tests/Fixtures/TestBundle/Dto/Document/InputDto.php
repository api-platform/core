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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\Document;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelatedDummy as DocumentRelatedDummy;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class InputDto
{
    /**
     * @var string
     */
    public $foo;

    /**
     * @var int
     */
    public $bar;

    /**
     * @var DocumentRelatedDummy[]
     */
    public $relatedDummies;
}
