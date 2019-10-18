<?php

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource
 * @ORM\Entity
 */
class ResourceWithInteger
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
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $myIntegerField = 0;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMyIntegerField(): int
    {
        return $this->myIntegerField;
    }

    /**
     * @param int $myIntegerField
     */
    public function setMyIntegerField(int $myIntegerField): void
    {
        $this->myIntegerField = $myIntegerField;
    }
}
