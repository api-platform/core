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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonLdRequiredConstructorInput',
    operations: [
        new Post(
            uriTemplate: '/jsonld_required_constructor_inputs',
            input: RequiredConstructorInputDto::class,
            output: false,
            processor: [self::class, 'process'],
        ),
    ],
)]
final class RequiredConstructorInputResource
{
    public static function process(RequiredConstructorInputDto $data): RequiredConstructorInputDto
    {
        return $data;
    }
}

final class RequiredConstructorInputDto
{
    public function __construct(
        public string $title,
        public int $rating,
        public string $comment,
    ) {
    }
}
