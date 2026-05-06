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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;

#[ApiResource(
    shortName: 'JsonLdInputOutputResource',
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_input_outputs',
            output: InputOutputDto::class,
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonld_input_outputs/{id}',
            uriVariables: ['id'],
            output: InputOutputDto::class,
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonld_input_outputs',
            input: InputOutputInputDto::class,
            output: InputOutputDto::class,
            processor: [self::class, 'process'],
        ),
        new Put(
            uriTemplate: '/jsonld_input_outputs/{id}',
            uriVariables: ['id'],
            input: InputOutputInputDto::class,
            output: InputOutputDto::class,
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
        ),
    ],
)]
class InputOutputResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?string $foo = null;

    public ?int $bar = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->foo = 'test';
        $r->bar = 1;

        return $r;
    }

    public static function provideCollection(): array
    {
        $a = new self();
        $a->id = 1;
        $a->foo = 'test';
        $a->bar = 1;
        $b = new self();
        $b->id = 2;
        $b->foo = 'test';
        $b->bar = 2;

        return [$a, $b];
    }

    public static function process(InputOutputInputDto $data, Operation $operation, array $uriVariables = [], array $context = []): InputOutputDto
    {
        $out = new InputOutputDto();
        $out->id = (int) ($uriVariables['id'] ?? 1);
        $out->bat = $data->foo;
        $out->baz = $data->bar;
        $out->relatedDummies = [];

        return $out;
    }
}

final class InputOutputInputDto
{
    public ?string $foo = null;

    public ?int $bar = null;
}

final class InputOutputDto
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?int $baz = null;

    public ?string $bat = null;

    /** @var list<string> */
    public array $relatedDummies = [];
}
