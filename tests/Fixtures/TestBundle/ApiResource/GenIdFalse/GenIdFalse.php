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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\Attribute\Ignore;

#[Get(uriTemplate: '/gen_id_falsy', provider: [self::class, 'getData'], normalizationContext: ['hydra_prefix' => false])]
class GenIdFalse
{
    public function __construct(public string $id, #[ApiProperty(genId: false)] public AggregateRating $aggregateRating, #[Ignore] public string $shouldBeIgnored = 'lala')
    {
    }

    public static function getData(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self('1', new AggregateRating(2, 3));
    }
}
