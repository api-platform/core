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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Resource linked to a standard object.
 *
 * @ODM\Document
 * @ApiResource
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ContainNonResource
{
    /**
     * @var mixed
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    public $id;

    /**
     * @var self
     */
    public $nested;

    /**
     * @var NotAResource
     */
    public $notAResource;
}
