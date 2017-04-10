<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Swagger\Util;

use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use ApiPlatform\Core\Swagger\Util\SwaggerOperationGenerator;
use Symfony\Component\Serializer\Tests\Fixtures\Dummy;

class SwaggerOperationGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $documentation = new Documentation(
            new ResourceNameCollection([Dummy::class]),
            'Test API',
            'This is a test API.',
            '1.2.3',
            ['jsonld' => ['application/ld+json']]
        );

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
                'put' => ['method' => 'PUT', 'denormalization_context' => ['groups' => 'dummy']],
            ],
            [
                'get' => ['method' => 'GET'],
                'post' => ['method' => 'POST'],
            ],
            []
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $operationPathResolverProphecy = $this->prophesize(OperationPathResolverInterface::class);
        $operationPathResolverProphecy->resolveOperationPath('Dummy', ['method' => 'GET'], false)->shouldBeCalled()->willReturn('/dummies/{id}.{_format}');
        $operationPathResolverProphecy->resolveOperationPath('Dummy', ['method' => 'PUT', 'denormalization_context' => ['groups' => 'dummy']], false)->shouldBeCalled()->willReturn('/dummies/{id}');
        $operationPathResolverProphecy->resolveOperationPath('Dummy', ['method' => 'GET'], true)->shouldBeCalled()->willReturn('/dummies.{_format}');
        $operationPathResolverProphecy->resolveOperationPath('Dummy', ['method' => 'POST'], true)->shouldBeCalled()->willReturn('/dummies');

        $operationMethodResolverProphecy = $this->prophesize(OperationMethodResolverInterface::class);
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getItemOperationMethod(Dummy::class, 'put')->shouldBeCalled()->willReturn('PUT');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'get')->shouldBeCalled()->willReturn('GET');
        $operationMethodResolverProphecy->getCollectionOperationMethod(Dummy::class, 'post')->willReturn('POST');

        $swaggerOperationGenerator = new SwaggerOperationGenerator(
            $resourceMetadataFactoryProphecy->reveal(),
            $operationPathResolverProphecy->reveal(),
            $operationMethodResolverProphecy->reveal()
        );

        $expectedData = [
            ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'put', 'operation' => ['method' => 'PUT', 'denormalization_context' => ['groups' => 'dummy']], 'isCollection' => false, 'path' => '/dummies/{id}', 'method' => 'PUT', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'get', 'operation' => ['method' => 'GET'], 'isCollection' => true, 'path' => '/dummies', 'method' => 'GET', 'mimeTypes' => ['application/ld+json']],
            ['resourceClass' => Dummy::class, 'operationName' => 'post', 'operation' => ['method' => 'POST'], 'isCollection' => true, 'path' => '/dummies', 'method' => 'POST', 'mimeTypes' => ['application/ld+json']],
        ];

        $this->assertEquals($expectedData, $swaggerOperationGenerator->generate($documentation));
    }
}
