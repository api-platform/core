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

namespace ApiPlatform\Elasticsearch\Tests\Fixtures;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;

/**
 * Foo.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource(operations: [new Get(), new Put(), new Patch(), new Delete(), new GetCollection(), new GetCollection(uriTemplate: 'custom_collection_desc_foos', order: ['name' => 'DESC']), new GetCollection(uriTemplate: 'custom_collection_asc_foos', order: ['name' => 'ASC'])], graphQlOperations: [new Query(name: 'item_query'), new QueryCollection(name: 'collection_query', paginationEnabled: false), new Mutation(name: 'create'), new Mutation(name: 'delete')], order: ['bar', 'name' => 'DESC'])]
class Foo
{
    /**
     * @var int The id
     */
    #[ApiProperty(identifier: true)]
    private ?int $id = null;

    /**
     * @var string The foo name
     */
    private $name;

    /**
     * @var string The foo bar
     */
    private $bar;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName($name): void
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

    public function setBar($bar): void
    {
        $this->bar = $bar;
    }
}
