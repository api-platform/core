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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Workbench\App\Http\Requests\GetDropOffSlotsRequest;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/slots/dropoff',
            rules: GetDropOffSlotsRequest::class,
            provider: [self::class, 'provide'],
            output: SlotsForDate::class,
        ),
    ],
)]
class SlotsForDate
{
    public int $id = 1;
    public string $name = 'Morning Slot';
    public string $note = 'This is a morning slot';

    public static function provide()
    {
        return [];
    }
}
