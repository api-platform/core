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
use ApiPlatform\Core\Annotation\Property;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Custom normalized dummy.
 *
 * @author Mikaël Labrut <labrut@gmail.com>
 *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"output"}},
 *     "denormalization_context"={"groups"={"input"}}
 * })
 * @ORM\Entity
 */
class CustomNormalizedDummy
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
     * @Groups({"input", "output"})
     */
    private $name;

    /**
     * @var string The dummy name alias.
     *
     * @ORM\Column(nullable=true)
     * @Property(iri="https://schema.org/alternateName")
     * @Groups({"input", "output"})
     */
    private $alias;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getPersonalizedAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $value
     */
    public function setPersonalizedAlias($value)
    {
        $this->alias = $value;
    }
}
