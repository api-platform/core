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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Concrete Dummy.
 *
 * @author Jérémy Derusse <jeremy@derusse.com>
 *
 * @ApiResource
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
