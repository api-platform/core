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
use ApiPlatform\Metadata\GetCollection;

#[Get(uriTemplate: '/multi_short_name/{id}', shortName: 'ItemShortName', provider: [self::class, 'provideItem'])]
#[GetCollection(uriTemplate: '/multi_short_name', shortName: 'ListShortName', provider: [self::class, 'provideCollection'])]
final class MultipleOperationShortNamesResource
{
    public ?int $id = null;

    public ?string $name = null;

    public static function provideItem(Get $operation, array $uriVariables = []): self
    {
        return self::create((int) $uriVariables['id']);
    }

    /**
     * @return self[]
     */
    public static function provideCollection(): array
    {
        return [self::create(1), self::create(2)];
    }

    private static function create(int $id): self
    {
        $resource = new self();
        $resource->id = $id;
        $resource->name = 'foo';

        return $resource;
    }
}
