<?php

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource
 * @ORM\Entity
 */
class ResourceWithFloat
{

    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $myFloatField = 0.0;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getMyFloatField(): float
    {
        return $this->myFloatField;
    }

    /**
     * @param float $myFloatField
     */
    public function setMyFloatField(float $myFloatField): void
    {
        $this->myFloatField = $myFloatField;
    }
}
