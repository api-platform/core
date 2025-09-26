<?php

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

    public static function provide() {
        return [];
    }
}
