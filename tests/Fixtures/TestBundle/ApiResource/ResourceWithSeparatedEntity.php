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

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SeparatedEntity;

#[ApiResource(shortName: 'SeparatedEntity', stateOptions: new Options(entityClass: SeparatedEntity::class))]
class ResourceWithSeparatedEntity
{
    public string $id;
    #[ApiFilter(OrderFilter::class)]
    public string $value;
}
