<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Tests\Fixtures\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Builder\Annotation\Resource;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ConcreteDummy.
 *
 * @author Jérémy Derusse <jeremy@derusse.com>
 *
 * @Resource
 * @ORM\Entity
 */
class ConcreteDummy extends AbstractDummy
{
    /**
     * @var string a concrete thing
     *
     * @ORM\Column
     * @Assert\NotBlank
     */
    private $instance;

    public function setInstance($instance)
    {
        $this->instance = $instance;
    }

    public function getInstance()
    {
        return $this->instance;
    }
}
