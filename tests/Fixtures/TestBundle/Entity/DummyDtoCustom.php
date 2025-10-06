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
use ApiPlatform\Tests\Fixtures\TestBundle\State\CustomInputDtoProcessor;
use ApiPlatform\Tests\Fixtures\TestBundle\State\CustomOutputDtoProvider;
use Doctrine\ORM\Mapping as ORM;

/**
 * DummyDtoCustom.
 */
#[ApiResource(
    operations: [
        new Get(),
        new Put(),
        new Delete(),
        new Post(input: CustomInputDto::class, processor: CustomInputDtoProcessor::class),
        new GetCollection(),
        new GetCollection(output: CustomOutputDto::class, uriTemplate: 'dummy_dto_custom_output', provider: CustomOutputDtoProvider::class, name: 'dummy_dto_custom_output_collection'),
        new Get(output: CustomOutputDto::class, uriTemplate: 'dummy_dto_custom_output/{id}', provider: CustomOutputDtoProvider::class),
        new Post(output: false, uriTemplate: 'dummy_dto_custom_post_without_output'),
    ]
)]
#[ORM\Entity]
class DummyDtoCustom
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    /**
     * @var string
     */
    #[ORM\Column]
    public $lorem;
    /**
     * @var string
     */
    #[ORM\Column]
    public $ipsum;

    public function getId(): ?int
    {
        return $this->id;
    }
}
