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
    operations: [
        new Get(provider: [self::class, 'getData']),
    ],
)]
class PageResource
{
    public string $id;

    /**
     * @var ModuleResource[]
     */
    public array $modules;

    public static function getData(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $resource = new self();
        $resource->id = 'page-1';

        $titleModule = new TitleModuleResource();
        $titleModule->id = 'title-module-1';
        $titleModule->title = 'My Title';

        $imageModule = new ImageModuleResource();
        $imageModule->id = 'image-module-1';
        $imageModule->url = 'http://example.com/image.jpg';

        $resource->modules = [$titleModule, $imageModule];

        return $resource;
    }
}
