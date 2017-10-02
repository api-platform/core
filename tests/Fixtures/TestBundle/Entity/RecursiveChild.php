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
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Recursive Child.
 *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"default"}},
 *     "denormalization_context"={"groups"={"default"}}
 * })
 * @ORM\Entity
 *
 * @author Brian Fox <brian@brianfox.fr>
 */
class RecursiveChild
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"default"})
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=30)
     * @Groups({"default"})
     */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity="Recursive", inversedBy="child")
     * @Groups({"default"})
     */
    public $parent;

    public function getId()
    {
        return $this->id;
    }
}
