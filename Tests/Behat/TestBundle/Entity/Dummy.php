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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Dunglas\ApiBundle\Annotation\Iri;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ORM\Entity
 */
class Dummy
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
     */
    private $name;
    /**
     * @var string The dummy name alias.
     *
     * @ORM\Column(nullable=true)
     * @Iri("https://schema.org/alternateName")
     */
    private $alias;
    /**
     * @var array foo
     */
    private $foo;
    /**
     * @var string A dummy.
     *
     * @ORM\Column(nullable=true)
     */
    public $dummy;
    /**
     * @var \DateTime A dummy date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime
     */
    public $dummyDate;
    /**
     * @var RelatedDummy A related dummy.
     *
     * @ORM\ManyToOne(targetEntity="RelatedDummy")
     */
    public $relatedDummy;
    /**
     * @var ArrayCollection Several dummies.
     *
     * @ORM\ManyToMany(targetEntity="RelatedDummy")
     */
    public $relatedDummies;
    /**
     * @var Array serialize data.
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    public $jsonData;
    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    public $nameConverted;

    public function __construct()
    {
        $this->relatedDummies = new ArrayCollection();
        $this->jsonData = [];
    }

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

    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function hasRole($role)
    {
    }

    public function setFoo(array $foo = null)
    {
    }

    public function setDummyDate(\DateTime $dummyDate)
    {
        $this->dummyDate = $dummyDate;
    }

    public function getDummyDate()
    {
        return $this->dummyDate;
    }

    public function setJsonData($jsonData)
    {
        $this->jsonData = $jsonData;
    }

    public function getJsonData()
    {
        return $this->jsonData;
    }
}
