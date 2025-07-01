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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(operations: [new Get(uriTemplate: '/levelfirst/{id}', provider: [self::class, 'provider'])])]
class LevelFirst
{
    /**
     * @param list<LevelSecond> $levelSecond
     */
    public function __construct(public string $id, #[ApiProperty(genId: false)] public array $levelSecond)
    {
    }

    public static function provider(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self($uriVariables['id'], [new LevelSecond(new LevelThird('3', 'L3 Name'))]);
    }
}
