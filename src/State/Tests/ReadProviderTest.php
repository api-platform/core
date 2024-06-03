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

namespace ApiPlatform\State\Tests;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\Provider\ReadProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;

class ReadProviderTest extends TestCase
{
    public function testWithoutRequest(): void
    {
        $operation = new GetCollection(read: true);
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('provide')->willReturn(['ok']);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);

        $readProvider = new ReadProvider($provider, $serializerContextBuilder);
        $this->assertEquals($readProvider->provide($operation), ['ok']);
    }
}
