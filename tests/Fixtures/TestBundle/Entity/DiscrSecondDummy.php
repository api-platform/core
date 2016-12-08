<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"discr_second_dummy"}},
 *         "denormalization_context"={"groups"={"discr_second_dummy"}}
 *     }
 * )
 * @ORM\Entity
 */
class DiscrSecondDummy extends DiscrAbstractDummy
{
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Groups({"discr_second_dummy", "discr_container_dummy"})
     */
    private $prop2;

    /**
     * @return string
     */
    public function getProp2()
    {
        return $this->prop2;
    }

    /**
     * @param string $prop2
     * @return static
     */
    public function setProp2($prop2)
    {
        $this->prop2 = $prop2;
        return $this;
    }
}