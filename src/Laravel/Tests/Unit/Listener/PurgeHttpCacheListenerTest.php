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

namespace ApiPlatform\Laravel\Tests\Unit\Listener;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\HttpCache\PurgeTagProviderInterface;
use ApiPlatform\Laravel\Eloquent\Listener\PurgeHttpCacheListener;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class PurgeHttpCacheListenerTest extends TestCase
{
    public function testPurgeTagProviders(): void
    {
        $model = new class extends Model {
        };

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())
            ->method('purge')
            ->with(['/models/1', '/models', '/parents/42/children']);

        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getIriFromResource')
            ->willReturnCallback(static function (object|string $resource, int $referenceType = 0, ?object $operation = null): string {
                if ($operation instanceof GetCollection) {
                    return '/models';
                }

                return '/models/1';
            });

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $provider = $this->createMock(PurgeTagProviderInterface::class);
        $provider->expects($this->once())
            ->method('getTagsForResource')
            ->with($model)
            ->willReturn(['/parents/42/children']);

        $listener = new PurgeHttpCacheListener($purger, $iriConverter, $resourceClassResolver, [$provider]);
        $listener->handleModelSaved('eloquent.saved: '.$model::class, [$model]);
        $listener->postFlush();
    }

    public function testPurgeTagProvidersOnDelete(): void
    {
        $model = new class extends Model {
        };

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())
            ->method('purge')
            ->with(['/models/1', '/models', '/parents/42/children']);

        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getIriFromResource')
            ->willReturnCallback(static function (object|string $resource, int $referenceType = 0, ?object $operation = null): string {
                if ($operation instanceof GetCollection) {
                    return '/models';
                }

                return '/models/1';
            });

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $provider = $this->createMock(PurgeTagProviderInterface::class);
        $provider->expects($this->once())
            ->method('getTagsForResource')
            ->with($model)
            ->willReturn(['/parents/42/children']);

        $listener = new PurgeHttpCacheListener($purger, $iriConverter, $resourceClassResolver, [$provider]);
        $listener->handleModelDeleted('eloquent.deleted: '.$model::class, [$model]);
        $listener->postFlush();
    }

    public function testNoTagsWhenNoProviders(): void
    {
        $model = new class extends Model {
        };

        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->never())->method('purge');

        $iriConverter = $this->createStub(IriConverterInterface::class);
        $iriConverter->method('getIriFromResource')->willThrowException(new InvalidArgumentException());

        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $listener = new PurgeHttpCacheListener($purger, $iriConverter, $resourceClassResolver);
        $listener->handleModelSaved('eloquent.saved: '.$model::class, [$model]);
        $listener->postFlush();
    }
}
