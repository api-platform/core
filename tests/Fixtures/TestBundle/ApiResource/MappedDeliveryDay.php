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
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DeliveryDay;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    operations: [new Get(), new Put()],
    stateOptions: new Options(entityClass: DeliveryDay::class),
)]
#[Map(target: DeliveryDay::class)]
final class MappedDeliveryDay
{
    #[Map(if: false)]
    public ?int $id = null;

    public string $day;
}
