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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6358;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    operations: [
        new GetCollection(
            output: OutputAndEntityClassDto::class,
            provider: [self::class, 'provide'],
            stateOptions: new Options(entityClass: OutputAndEntityClassEntity::class)
        ),
    ],
)]
class OutputAndEntityClass
{
    public static function provide(): array
    {
        return [new OutputAndEntityClassEntity(1)];
    }
}
