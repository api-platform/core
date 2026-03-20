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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Validator\CountableConstraint;

/**
 * Resource without ObjectMapper to test that validation runs only once.
 *
 * The CountableConstraint validator increments a static counter on each call.
 * Without the fix, ValidateProvider + ValidateProcessor both trigger validation,
 * causing the counter to reach 2 instead of 1.
 */
#[Post(
    shortName: 'ValidateOnce',
    uriTemplate: '/validate_once',
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
#[Get(
    shortName: 'ValidateOnce',
    uriTemplate: '/validate_once/{id}',
    provider: [self::class, 'provide'],
)]
class ValidateOnce
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    #[CountableConstraint]
    public string $name = '';

    public static function provide(): self
    {
        $s = new self();
        $s->id = 1;
        $s->name = 'test';

        return $s;
    }

    public static function process(self $data): self
    {
        $data->id = 1;

        return $data;
    }
}
