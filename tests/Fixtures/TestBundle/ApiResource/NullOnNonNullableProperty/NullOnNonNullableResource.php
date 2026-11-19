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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\NullOnNonNullableProperty;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

#[Get(
    shortName: 'NullOnNonNullableResource',
    uriTemplate: '/null_on_non_nullable_resources/{id}',
    provider: [self::class, 'provide'],
)]
#[Post(
    shortName: 'NullOnNonNullableResource',
    uriTemplate: '/null_on_non_nullable_resources',
    processor: [self::class, 'process'],
    denormalizationContext: [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true],
)]
#[Post(
    shortName: 'NullOnNonNullableResource',
    uriTemplate: '/null_on_non_nullable_resources_collect',
    processor: [self::class, 'process'],
    denormalizationContext: [
        AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
        DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
    ],
)]
class NullOnNonNullableResource
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public string $name;

    public static function provide(): self
    {
        $r = new self();
        $r->name = 'foo';

        return $r;
    }

    public static function process(self $data): self
    {
        return $data;
    }
}
