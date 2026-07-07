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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FetchDataFalseStateOptionsEntity;

/**
 * DTO resource whose class differs from its Doctrine entity: the identifier link's fromClass is this
 * resource class, so getReferenceIdentifiers() must compare against it (not the entity class) for the
 * fetch_data=false reference fast-path to trigger.
 */
#[ApiResource(
    shortName: 'FetchDataFalseStateOptions',
    operations: [new Get()],
    stateOptions: new Options(entityClass: FetchDataFalseStateOptionsEntity::class),
)]
class FetchDataFalseStateOptions
{
    public ?int $id = null;

    public ?string $name = null;
}
