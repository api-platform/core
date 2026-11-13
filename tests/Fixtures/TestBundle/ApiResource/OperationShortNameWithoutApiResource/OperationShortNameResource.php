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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\OperationShortNameWithoutApiResource;

use ApiPlatform\Metadata\Get;

#[Get(uriTemplate: '/operation_short_name_resources/{id}', shortName: 'CustomShortName', provider: [self::class, 'provide'])]
final class OperationShortNameResource
{
    public ?int $id = null;

    public ?string $name = null;

    public static function provide(Get $operation, array $uriVariables = []): self
    {
        $resource = new self();
        $resource->id = (int) $uriVariables['id'];
        $resource->name = 'foo';

        return $resource;
    }
}
