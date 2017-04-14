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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Resource linked to a standard object.
 *
 * @ORM\Entity
 * @ApiResource
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ContainNonResource
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
