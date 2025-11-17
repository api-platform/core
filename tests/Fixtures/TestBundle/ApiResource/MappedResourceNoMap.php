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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MappedEntityNoMap;

#[ApiResource(
    operations: [
        new Get(map: false, uriVariables: ['id'], provider: [self::class, 'provide']),
        new Post(map: false),
    ],
    stateOptions: new Options(entityClass: MappedEntityNoMap::class),
    normalizationContext: [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false],
)]
class MappedResourceNoMap
{
    public function __construct(public ?int $id = null, public ?string $name = null)
    {
    }

    public static function provide(Operation $operation, array $uriVariables = [])
    {
        return new self($uriVariables['id'], 'test name');
    }
}
