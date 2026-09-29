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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8115;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    shortName: 'Modules',
    operations: [
        new GetCollection(
            uriTemplate: '/issue8115/modules',
            name: 'issue8115_modules_get_collection',
            class: Module::class,
            normalizationContext: ['groups' => ['module']],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class ReadModuleResource
{
    public static function provide(): iterable
    {
        return [];
    }
}
