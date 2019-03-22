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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DummyProperty.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 *
 * @ODM\Document
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
     * @ODM\Id(strategy="INCREMENT", type="integer")
     *
     * @Groups({"dummy_read", "dummy_graphql_read"})
     */
    private $id;

    /**
     * @var string
     *
     * @ODM\Field(nullable=true)
     *
     * @Groups({"dummy_read", "dummy_write"})
     */
    public $foo;

    /**
     * @var string
     *
     * @ODM\Field(nullable=true)
     *
     * @Groups({"dummy_read", "dummy_graphql_read", "dummy_write"})
     */
    public $bar;

    /**
     * @var string
     *
     * @ODM\Field(nullable=true)
     *
     * @Groups({"dummy_read", "dummy_graphql_read", "dummy_write"})
     */
    public $baz;

    /**
     * @var DummyGroup
     *
     * @ODM\ReferenceOne(targetDocument=DummyGroup::class, cascade={"persist"}, nullable=true)
     *
     * @Groups({"dummy_read", "dummy_graphql_read", "dummy_write"})
     */
    public $group;

    /**
     * @var DummyGroup[]
     *
     * @ODM\ReferenceMany(targetDocument=DummyGroup::class, cascade={"persist"})
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
