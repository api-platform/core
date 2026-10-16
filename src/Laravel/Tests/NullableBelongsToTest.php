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

namespace ApiPlatform\Laravel\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\DeliveryRequest;
use Workbench\App\Models\NullableMorphNote;
use Workbench\Database\Factories\NullableMorphNoteFactory;
use Workbench\Database\Factories\TimeSlotFactory;

/**
 * @see https://github.com/api-platform/core/issues/8664
 */
final class NullableBelongsToTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    public function testPostWithExplicitNullBelongsTo(): void
    {
        $response = $this->postJson('/api/delivery_requests', [
            'pickupTimeSlot' => null,
            'note' => 'no slot',
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);

        $response->assertStatus(201);
        $this->assertNull(DeliveryRequest::query()->sole()->pickup_time_slot_id); // @phpstan-ignore-line
    }

    public function testPatchWithExplicitNullClearsBelongsTo(): void
    {
        $iri = $this->createDeliveryRequestWithSlot();

        $response = $this->patchJson($iri, [
            'pickupTimeSlot' => null,
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/merge-patch+json']);

        $response->assertStatus(200);
        $this->assertNull(DeliveryRequest::query()->sole()->pickup_time_slot_id); // @phpstan-ignore-line
    }

    public function testOmittingBelongsToKeepsIt(): void
    {
        $iri = $this->createDeliveryRequestWithSlot();
        $slotId = DeliveryRequest::query()->sole()->pickup_time_slot_id; // @phpstan-ignore-line

        $response = $this->patchJson($iri, [
            'note' => 'updated',
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/merge-patch+json']);

        $response->assertStatus(200);
        $deliveryRequest = DeliveryRequest::query()->sole();
        $this->assertSame($slotId, $deliveryRequest->pickup_time_slot_id); // @phpstan-ignore-line
        $this->assertSame('updated', $deliveryRequest->note); // @phpstan-ignore-line
    }

    public function testPatchWithExplicitNullClearsMorphTo(): void
    {
        $slot = TimeSlotFactory::new()->createOne();
        $note = NullableMorphNoteFactory::new()->for($slot, 'noteable')->create();
        $this->assertNotNull(NullableMorphNote::query()->sole()->noteable_id); // @phpstan-ignore-line

        $response = $this->patchJson('/api/nullable_morph_notes/'.$note->id, [ // @phpstan-ignore-line
            'noteable' => null,
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/merge-patch+json']);

        $response->assertStatus(200);
        $note = NullableMorphNote::query()->sole();
        $this->assertNull($note->noteable_id); // @phpstan-ignore-line
        $this->assertNull($note->noteable_type); // @phpstan-ignore-line
    }

    private function createDeliveryRequestWithSlot(): string
    {
        $slot = TimeSlotFactory::new()->create();

        $response = $this->postJson('/api/delivery_requests', [
            'pickupTimeSlot' => '/api/time_slots/'.$slot->id, // @phpstan-ignore-line
            'note' => 'with slot',
        ], ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']);
        $response->assertStatus(201);
        $this->assertNotNull(DeliveryRequest::query()->sole()->pickup_time_slot_id); // @phpstan-ignore-line

        return $response->json('@id');
    }
}
