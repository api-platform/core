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

use Doctrine\ORM\Mapping as ORM;

/**
 * Class NonApiResourceDummy
 *
 * Provides a simple ORM entity that is not an API resource at the same time.
 *
 * @author Michael Petri <mpetri@lyska.io>
 * @see https://github.com/api-platform/core/pull/1936#pullrequestreview-119415360
 *
 * @ORM\Entity
 */
class NonApiResourceDummy
{

    /**
     * The unique entity id.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Gets the unique entity id.
     *
     * @return int
     *   The unique entity id.
     */
    public function getId()
    {
        return $this->id;
    }

}
