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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\CreateItemAction;
use ApiPlatform\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\DoubleBatAction;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\Document\OutputDto;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * DummyDtoNoInput.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
#[ApiResource(operations: [new Get(), new Delete(), new Post(uriTemplate: '/dummy_dto_no_inputs/{id}/double_bat', controller: DoubleBatAction::class, status: 200), new Post(uriTemplate: '/dummy_dto_no_inputs', controller: CreateItemAction::class), new GetCollection()], input: false, output: OutputDto::class)]
#[ODM\Document]
class DummyDtoNoInput
{
    /**
     * @var int The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var string
     */
    #[ODM\Field]
    public $lorem;
    /**
     * @var float
     */
    #[ODM\Field(type: 'float')]
    public $ipsum;

    public function getId()
    {
        return $this->id;
    }
}
