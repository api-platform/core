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

/**
 * Foo.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @ApiResource(
 *     attributes={
 *         "order"={"bar", "name"="DESC"}
 *     },
 *     graphql={
 *         "item_query",
 *         "collection_query"={"pagination_enabled"=false},
 *         "create",
 *         "delete"
 *     },
 *     collectionOperations={
 *         "get",
 *         "get_desc_custom"={"method"="GET", "path"="custom_collection_desc_foos", "order"={"name"="DESC"}},
 *         "get_asc_custom"={"method"="GET", "path"="custom_collection_asc_foos", "order"={ "name"="ASC"}},
 *     }
 * )
 * @ODM\Document
 */
class Foo
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var string The foo name
     *
     * @ODM\Field
     */
    private $name;

    /**
     * @var string The foo bar
     *
     * @ODM\Field
     */
    private $bar;

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

    public function getBar()
    {
        return $this->bar;
    }

    public function setBar($bar)
    {
        $this->bar = $bar;
    }
}
