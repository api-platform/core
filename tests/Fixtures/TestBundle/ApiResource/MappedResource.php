<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(stateOptions: new Options(entityClass: MappedEntity::class), normalizationContext: [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false])]
#[Map(target: MappedEntity::class)]
final class MappedResource
{
    #[Map(if: false)]
    public ?string $id = null;

    #[Map(target: 'firstName', transform: [self::class, 'toFirstName'])]
    #[Map(target: 'lastName', transform: [self::class, 'toLastName'])]
    public string $username;

    public static function toFirstName(string $v): string {
        return explode(' ', $v)[0] ?? null;
    }

    public static function toLastName(string $v): string {
        return explode(' ', $v)[1] ?? null;
    }
}
