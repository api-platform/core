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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Overrode Operation Dummy.
 *
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"overrode_operation_dummy_read"}},
 *         "denormalization_context"={"groups"={"overrode_operation_dummy_write"}}
 *     },
 *     itemOperations={
 *         "get"={
 *             "method"="GET",
 *             "normalization_context"={"groups"={"overrode_operation_dummy_get"}},
 *             "denormalization_context"={"groups"={"overrode_operation_dummy_get"}}
 *         },
 *         "put"={
 *             "method"="PUT",
 *             "normalization_context"={"groups"={"overrode_operation_dummy_put"}},
 *             "denormalization_context"={"groups"={"overrode_operation_dummy_put"}}
 *          },
 *          "delete"={"method"="DELETE"}
 *     }
 * )
 * @ORM\Entity
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class OverrodeOperationDummy
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
     * @Groups({"overrode_operation_dummy_read", "overrode_operation_dummy_write", "overrode_operation_dummy_get"})
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var string The dummy name alias.
     *
     * @ORM\Column(nullable=true)
     * @Groups({"overrode_operation_dummy_read", "overrode_operation_dummy_put", "overrode_operation_dummy_get"})
     * @ApiProperty(iri="https://schema.org/alternateName")
     */
    private $alias;

    /**
     * @var string A short description of the item.
     *
     * @ORM\Column(nullable=true)
     * @Groups({"overrode_operation_dummy_read" ,"overrode_operation_dummy_write", "overrode_operation_dummy_get", "overrode_operation_dummy_put"})
     * @ApiProperty(iri="https://schema.org/description")
     */
    public $description;

    /**
     * @ORM\Column(nullable=true)
     * @Groups({"overrode_operation_dummy_write"})
     */
    public $notGettable;

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

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
