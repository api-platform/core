<?php

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource
 * @ORM\Entity
 */
class ResourceWithBoolean
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
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $myBooleanField = false;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function getMyBooleanField(): bool
    {
        return $this->myBooleanField;
    }

    /**
     * @param bool $myBooleanField
     */
    public function setMyBooleanField(bool $myBooleanField): void
    {
        $this->myBooleanField = $myBooleanField;
    }
}
