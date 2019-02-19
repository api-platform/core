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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\CustomInputDto;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\CustomOutputDto;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * DummyDtoCustom.
 *
 * @ODM\Document
 *
 * @ApiResource(
 *     collectionOperations={"post"={"input"=CustomInputDto::class}, "get", "custom_output"={"output"=CustomOutputDto::class, "path"="dummy_dto_custom_output", "method"="GET"}, "post_without_output"={"output"=false, "method"="POST", "path"="dummy_dto_custom_post_without_output"}},
 *     itemOperations={"get", "custom_output"={"output"=CustomOutputDto::class, "method"="GET", "path"="dummy_dto_custom_output/{id}"}, "put", "delete"}
 * )
 */
class DummyDtoCustom
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ODM\Field
     */
    public $lorem;

    /**
     * @var string
     *
     * @ODM\Field
     */
    public $ipsum;

    public function getId()
    {
        return $this->id;
    }
}
