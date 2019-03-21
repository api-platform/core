<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DummyProperty.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 *
 * @ORM\Entity
 *
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"dummy_read"}},
 *         "denormalization_context"={"groups"={"dummy_write"}},
 *         "filters"={
 *             "dummy_property.property",
 *             "dummy_property.whitelist_property",
 *             "dummy_property.whitelisted_properties"
 *         }
 *     },
 *     graphql={
 *         "query",
 *         "update",
 *         "delete",
 *         "create"={
 *             "normalization_context"={"groups"={"dummy_graphql_read"}},
 *         }
 *     }
 * )
 */
class DummyProperty
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"dummy_read", "dummy_graphql_read"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     *
     * @Groups({"dummy_read", "dummy_write"})
     */
    public $foo;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     *
     * @Groups({"dummy_read", "dummy_graphql_read", "dummy_write"})
     */
    public $bar;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     *
     * @Groups({"dummy_read", "dummy_graphql_read", "dummy_write"})
     */
    public $baz;

    /**
     * @var DummyGroup
     *
     * @ORM\ManyToOne(targetEntity=DummyGroup::class, cascade={"persist"})
     *
     * @Groups({"dummy_read", "dummy_graphql_read", "dummy_write"})
     */
    public $group;

    /**
     * @var DummyGroup[]
     *
     * @ORM\ManyToMany(targetEntity=DummyGroup::class, cascade={"persist"})
     * @Groups({"dummy_read", "dummy_graphql_read", "dummy_write"})
     */
    public $groups;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
