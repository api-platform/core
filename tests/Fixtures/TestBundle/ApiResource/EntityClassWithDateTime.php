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
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    operations  : [
        new Get(
            uriTemplate: '/EntityClassWithDateTime/{id}',
        ),
        new GetCollection(
            uriTemplate: '/EntityClassWithDateTime',
        ),
    ],
    stateOptions: new Options(entityClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\EntityClassWithDateTime::class)
)]
class EntityClassWithDateTime
{
    public ?int $id;
    public ?\DateTimeInterface $start;
}
