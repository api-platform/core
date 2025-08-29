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

namespace ApiPlatform\Symfony\Tests\Fixtures;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    stateOptions: new Options(entityClass: MappedEntity::class),
    normalizationContext: [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false],
)]
#[Map(target: MappedEntity::class)]
final class MappedResource
{
    #[Map(if: false)]
    public ?string $id = null;

    #[Map(target: 'firstName', transform: [self::class, 'toFirstName'])]
    #[Map(target: 'lastName', transform: [self::class, 'toLastName'])]
    public string $username;

    public static function toFirstName(string $v): string
    {
        return explode(' ', $v)[0];
    }

    public static function toLastName(string $v): string
    {
        return explode(' ', $v)[1];
    }
}
