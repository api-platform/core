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

use ApiPlatform\Core\Annotation\Iri;
use ApiPlatform\Core\Annotation\Property;
use ApiPlatform\Core\Annotation\Resource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AbstractDummy.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @Resource(attributes={"filters": {"my_dummy.search", "my_dummy.order", "my_dummy.date"}})
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string", length=16)
 * @ORM\DiscriminatorMap({"concrete" = "ConcreteDummy"})
 */
abstract class AbstractDummy
{
    /**
     * @var int The id.
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string The dummy name.
     *
     * @ORM\Column
     * @Assert\NotBlank
     * @Property(iri="http://schema.org/name")
     */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
