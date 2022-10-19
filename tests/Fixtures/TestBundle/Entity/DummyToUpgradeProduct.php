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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ApiResource
 */
class DummyToUpgradeProduct
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Collection<int,DummyToUpgradeWithOnlyAnnotation>
     * @ORM\OneToMany(mappedBy="dummyToUpgradeProduct", targetEntity=DummyToUpgradeWithOnlyAnnotation::class)
     */
    private $dummysToUpgradeWithOnlyAnnotation;

    /**
     * @var Collection<int,DummyToUpgradeWithOnlyAttribute>
     * @ORM\OneToMany(mappedBy="dummyToUpgradeProduct", targetEntity=DummyToUpgradeWithOnlyAttribute::class)
     */
    private $dummysToUpgradeWithOnlyAttribute;
}
