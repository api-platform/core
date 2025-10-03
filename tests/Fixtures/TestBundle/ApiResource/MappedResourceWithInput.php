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

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\MappedResouceInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Post(
    uriTemplate: '/mapped_resource_with_input',
    input: MappedResouceInput::class,
    stateOptions: new Options(entityClass: MappedEntity::class),
    normalizationContext: [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false],
    processor: [self::class, 'process']
)]
#[Map(target: MappedEntity::class)]
final class MappedResourceWithInput
{
    #[Map(if: false)]
    public ?string $id = null;
    public string $username;

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $s = new self();
        $s->id = $data->id;
        $s->username = $data->name;

        return $s;
    }
}
