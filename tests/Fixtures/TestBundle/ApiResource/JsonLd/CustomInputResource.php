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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'JsonLdCustomInput',
    operations: [
        new Get(
            uriTemplate: '/jsonld_custom_inputs/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonld_custom_inputs',
            input: CustomInputDto::class,
            processor: [self::class, 'process'],
        ),
    ],
)]
class CustomInputResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?string $lorem = null;

    public ?string $ipsum = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->lorem = 'test';
        $r->ipsum = '1';

        return $r;
    }

    public static function process(CustomInputDto $data): self
    {
        $r = new self();
        $r->id = 1;
        $r->lorem = $data->foo;
        $r->ipsum = (string) $data->bar;

        return $r;
    }
}

final class CustomInputDto
{
    public ?string $foo = null;

    #[Assert\Type('integer')]
    public ?int $bar = null;
}
