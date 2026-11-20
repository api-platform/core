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

namespace Workbench\App\ApiResource;

use ApiPlatform\Laravel\workbench\app\Enums\TicketPriority;
use ApiPlatform\Laravel\workbench\app\Enums\TicketStatus;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;

#[GetCollection(
    uriTemplate: '/with_enum_class_parameters',
    parameters: [
        'status' => new QueryParameter(enumClass: TicketStatus::class),
        'priority' => new QueryParameter(enumClass: TicketPriority::class),
    ],
    provider: [self::class, 'provide'],
)]
class WithEnumClassParameter
{
    public int $id = 1;

    /**
     * @return list<self>
     */
    public static function provide(): array
    {
        return [];
    }
}
