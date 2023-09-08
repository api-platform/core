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

use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Odm\State\Options as ODMOptions;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\SeparatedEntity;

#[ApiResource(shortName: 'SeparatedDocument', stateOptions: new ODMOptions(documentClass: SeparatedEntity::class))]
class ResourceWithSeparatedDocument
{
    public string $id;
    #[ApiFilter(OrderFilter::class)]
    public string $value;
}
