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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\InputDtoWithNameConverter;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\OutputDtoWithNameConverter;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GenderTypeEnum;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/dummy_dto_name_converted/{id}',
            output: OutputDtoWithNameConverter::class,
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/dummy_dto_name_converted',
            input: InputDtoWithNameConverter::class,
            processor: [self::class, 'process'],
            provider: [self::class, 'provide'],
        ),
    ]
)]
class DummyDtoNameConverted
{
    public function __construct(
        public ?int $id = null,
        public ?string $nameConverted = null,
        public ?GenderTypeEnum $gender = null,
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self(id: 1, nameConverted: 'converted', gender: GenderTypeEnum::MALE);
    }

    /**
     * @param InputDtoWithNameConverter $data
     */
    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self(id: 1, nameConverted: $data->nameConverted);
    }
}
