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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use Symfony\Component\Validator\Constraints as Assert;

#[Get(
    uriTemplate: '/cached_metadata_secured_links/{id}',
    uriVariables: [
        'id' => new Link(
            fromClass: self::class,
            identifiers: ['id'],
            security: 'id == 1',
            provider: [self::class, 'skipLinkResolution'],
        ),
    ],
    provider: [self::class, 'provide'],
)]
#[Get(
    uriTemplate: '/cached_metadata_constrained_links/{id}',
    uriVariables: [
        'id' => new Link(
            fromClass: self::class,
            identifiers: ['id'],
            constraints: [new Assert\Positive()],
        ),
    ],
    provider: [self::class, 'provide'],
)]
final class CachedMetadataLinkResource
{
    public string $id = '';

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        $resource = new self();
        $resource->id = (string) $uriVariables['id'];

        return $resource;
    }

    public static function skipLinkResolution(Parameter $parameter, array $values = [], array $context = []): null
    {
        return null;
    }
}
