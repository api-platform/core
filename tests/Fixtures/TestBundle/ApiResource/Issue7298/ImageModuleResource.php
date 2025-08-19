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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7298;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'ImageModule',
    operations: [
        new Get(provider: [self::class, 'getData']),
    ],
)]
class ImageModuleResource extends ModuleResource
{
    public string $url;

    public static function getData(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $resource = new self();
        $resource->id = 'image-module-1';
        $resource->url = 'http://example.com/image.jpg';

        return $resource;
    }
}
