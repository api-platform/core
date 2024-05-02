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

namespace ApiPlatform\State\Tests\Provider;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\Provider\ReadProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ReadProviderTest extends TestCase
{
    public function testSetsSerializerContext(): void
    {
        $data = new \stdClass();
        $operation = new Get(read: true);
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($data);
        $serializerContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->expects($this->once())->method('createFromRequest')->willReturn(['a']);
        $provider = new ReadProvider($decorated, $serializerContextBuilder);
        $request = new Request();
        $provider->provide($operation, ['id' => 1], ['request' => $request]);
        $this->assertEquals(['a'], $request->attributes->get('_api_normalization_context'));
    }
}
