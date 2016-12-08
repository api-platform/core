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
 *         "normalization_context"={"groups"={"discr_first_dummy"}},
 *         "denormalization_context"={"groups"={"discr_first_dummy"}}
 *     }
 * )
 * @ORM\Entity
 */
class DiscrFirstDummy extends DiscrAbstractDummy
{
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Groups({"discr_first_dummy", "discr_container_dummy"})
     */
    private $prop1;

    /**
     * @return string
     */
    public function getProp1()
    {
        return $this->prop1;
    }

    /**
     * @param string $prop1
     * @return static
     */
    public function setProp1($prop1)
    {
        $this->prop1 = $prop1;
        return $this;
    }
}