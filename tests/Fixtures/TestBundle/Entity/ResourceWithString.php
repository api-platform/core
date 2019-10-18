<?php

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource
 * @ORM\Entity
 */
class ResourceWithString
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
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $myStringField = '';

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMyStringField(): string
    {
        return $this->myStringField;
    }

    /**
     * @param string $myStringField
     */
    public function setMyStringField(string $myStringField): void
    {
        $this->myStringField = $myStringField;
    }
}
