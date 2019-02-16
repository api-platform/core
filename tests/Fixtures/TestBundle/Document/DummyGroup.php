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
 * DummyGroup.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @ODM\Document
 *
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"dummy_read"}},
 *         "denormalization_context"={"groups"={"dummy_write"}},
 *         "filters"={
 *             "dummy_group.group",
 *             "dummy_group.override_group",
 *             "dummy_group.whitelist_group",
 *             "dummy_group.override_whitelist_group"
 *         }
 *     },
 *     graphql={
 *         "query"={"normalization_context"={"groups"={"dummy_foo"}}},
 *         "delete",
 *         "create"={
 *             "normalization_context"={"groups"={"dummy_bar"}},
 *             "denormalization_context"={"groups"={"dummy_bar", "dummy_baz"}}
 *         }
 *     }
 * )
 */
class DummyGroup
{
    /**
     * @var int
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     *
     * @Groups({"dummy", "dummy_read", "dummy_id"})
     */
    private $id;

    /**
     * @var string
     *
     * @ODM\Field(nullable=true)
     *
     * @Groups({"dummy", "dummy_read", "dummy_write", "dummy_foo"})
     */
    public $foo;

    /**
     * @var string
     *
     * @ODM\Field(nullable=true)
     *
     * @Groups({"dummy", "dummy_read", "dummy_write", "dummy_bar"})
     */
    public $bar;

    /**
     * @var string
     *
     * @ODM\Field(nullable=true)
     *
     * @Groups({"dummy", "dummy_read", "dummy_baz"})
     */
    public $baz;

    /**
     * @var string
     *
     * @ODM\Field(nullable=true)
     *
     * @Groups({"dummy", "dummy_write", "dummy_qux"})
     */
    public $qux;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
