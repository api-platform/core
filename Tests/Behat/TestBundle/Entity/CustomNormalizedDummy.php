<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Dunglas\ApiBundle\Annotation\Iri;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Custom normalized dummy.
 *
 * @author Mikaël Labrut <labrut@gmail.com>
 *
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
     * @Iri("http://schema.org/name")
     * @Groups({"input", "output"})
     */
    private $name;
    /**
     * @var string The dummy name alias.
     *
     * @ORM\Column(nullable=true)
     * @Iri("https://schema.org/alternateName")
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

    /**n
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
