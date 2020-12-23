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
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ApiResource(mercure=true)
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DummyMercure
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column
     */
    public $name;

    /**
     * @ORM\Column
     */
    public $description;

    /**
     * @ORM\ManyToOne(targetEntity="RelatedDummy")
     */
    public $relatedDummy;
}
