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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6264;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(normalizationContext: ['groups' => ['get']])]
#[GetCollection(provider: AvailabilityStatus::class.'::getCases')]
#[Get(provider: AvailabilityStatus::class.'::getCase')]
enum AvailabilityStatus: string
{
    use BackedEnumStringTrait;

    case Pending = 'pending';
    case Reviewed = 'reviewed';
}
