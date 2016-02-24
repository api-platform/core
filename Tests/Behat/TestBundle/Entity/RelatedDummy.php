<?php

/*
 * This file is part of the API Platform project.
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
 * Related dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ORM\Entity
 * @Iri("https://schema.org/Product")
 */
class RelatedDummy extends ParentDummy
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string A name.
     *
     * @ORM\Column(nullable=true)
     */
    public $name;
    /**
     * @ORM\Column
     * @Groups({"barcelona", "chicago"})
     */
    protected $symfony = 'symfony';
    /**
     * @var \DateTime A dummy date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime
     */
    public $dummyDate;
    /**
     * @ORM\ManyToOne(targetEntity="ThirdLevel", cascade={"persist"})
     * @Groups({"barcelona", "chicago"})
     */
    public $thirdLevel;
    /**
     * @ORM\ManyToOne(targetEntity="UnknownDummy", cascade={"persist"})
     */
    public $unknown;

    public function setUnknown()
    {
        $this->unknown = new UnknownDummy();
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

    public function getSymfony()
    {
        return $this->symfony;
    }

    public function setSymfony($symfony)
    {
        $this->symfony = $symfony;
    }

    public function setDummyDate(\DateTime $dummyDate)
    {
        $this->dummyDate = $dummyDate;
    }

    public function getDummyDate()
    {
        return $this->dummyDate;
    }
}
