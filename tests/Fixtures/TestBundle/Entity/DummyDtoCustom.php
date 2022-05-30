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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\CustomInputDto;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\CustomOutputDto;
use Doctrine\ORM\Mapping as ORM;

/**
 * DummyDtoCustom.
 *
 * @ORM\Entity
 */
#[ApiResource(operations: [new Get(), new Get(output: CustomOutputDto::class, uriTemplate: 'dummy_dto_custom_output/{id}'), new Put(), new Delete(), new Post(input: CustomInputDto::class), new GetCollection(), new GetCollection(output: CustomOutputDto::class, uriTemplate: 'dummy_dto_custom_output'), new Post(output: false, uriTemplate: 'dummy_dto_custom_post_without_output')])]
class DummyDtoCustom
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
     * @var string
     *
     * @ORM\Column
     */
    public $lorem;
    /**
     * @var string
     *
     * @ORM\Column
     */
    public $ipsum;

    public function getId()
    {
        return $this->id;
    }
}
