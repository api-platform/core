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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\CreateItemAction;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput\DoubleBatAction;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\Document\OutputDto;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * DummyDtoNoInput.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @ODM\Document
 *
 * @ApiResource(
 *     attributes={
 *         "input"=false,
 *         "output"=OutputDto::class
 *     },
 *     collectionOperations={
 *         "post"={
 *             "method"="POST",
 *             "path"="/dummy_dto_no_inputs",
 *             "controller"=CreateItemAction::class,
 *         },
 *         "get",
 *     },
 *     itemOperations={
 *         "get",
 *         "delete",
 *         "post_double_bat"={
 *             "method"="POST",
 *             "path"="/dummy_dto_no_inputs/{id}/double_bat",
 *             "controller"=DoubleBatAction::class,
 *             "status"=200,
 *         },
 *     },
 * )
 */
class DummyDtoNoInput
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private $id;

    /**
     * @var string
     *
     * @ODM\Field
     */
    public $lorem;

    /**
     * @var float
     *
     * @ODM\Field(type="float")
     */
    public $ipsum;

    public function getId()
    {
        return $this->id;
    }
}
