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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\ErrorResourceAttributeLoaderPass;
use ApiPlatform\Validator\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @see https://github.com/api-platform/core/issues/8174
 */
final class ErrorResourceAttributeLoaderPassTest extends TestCase
{
    public function testRegistersAttributeLoaderForErrorClassesInChainLoader(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('serializer.mapping.chain_loader', new Definition(LoaderChain::class, [[]]));
        $container->setDefinition('serializer.mapping.cache_warmer', new Definition(\stdClass::class, [[]]));

        (new ErrorResourceAttributeLoaderPass())->process($container);

        $chainLoader = $container->getDefinition('serializer.mapping.chain_loader');
        $loaders = $chainLoader->getArgument(0);
        $this->assertCount(1, $loaders);
        $loaderDefinition = $loaders[0];
        $this->assertInstanceOf(Definition::class, $loaderDefinition);
        $this->assertSame(AttributeLoader::class, $loaderDefinition->getClass());

        // Argument 0 is allowAnyClass (must be true so the loader doesn't return early when entered through a parent class)
        $this->assertTrue($loaderDefinition->getArgument(0));

        // Argument 1 is mappedClasses (must include api-platform's Error and ValidationException)
        $mappedClasses = $loaderDefinition->getArgument(1);
        $this->assertArrayHasKey(Error::class, $mappedClasses);
        $this->assertArrayHasKey(ValidationException::class, $mappedClasses);
    }

    public function testAlsoUpdatesCacheWarmer(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('serializer.mapping.chain_loader', new Definition(LoaderChain::class, [[]]));
        $container->setDefinition('serializer.mapping.cache_warmer', new Definition(\stdClass::class, [[]]));

        (new ErrorResourceAttributeLoaderPass())->process($container);

        $loaders = $container->getDefinition('serializer.mapping.cache_warmer')->getArgument(0);
        $this->assertCount(1, $loaders);
        $this->assertSame(AttributeLoader::class, $loaders[0]->getClass());
    }

    public function testDoesNothingWhenChainLoaderIsAbsent(): void
    {
        $container = new ContainerBuilder();

        (new ErrorResourceAttributeLoaderPass())->process($container);

        $this->assertFalse($container->hasDefinition('serializer.mapping.chain_loader'));
    }

    /**
     * Mirrors the runtime behavior with `framework.serializer.enable_attributes: false`:
     * Symfony builds the `AttributeLoader` with `allowAnyClass = false` and no mapped classes,
     * so no metadata is loaded for the api-platform error resources, and the normalizer
     * returns an empty payload. Once the dedicated loader is added to the chain, the metadata
     * is loaded again and the payload contains the expected fields.
     */
    public function testErrorNormalizationStaysPopulatedWhenAttributesAreDisabled(): void
    {
        // Simulates `enable_attributes: false`: Symfony's attribute loader rejects every class.
        $disabledAttributeLoader = new AttributeLoader(allowAnyClass: false, mappedClasses: []);

        $emptyChain = new LoaderChain([$disabledAttributeLoader]);
        $serializerEmpty = new Serializer(
            [new ObjectNormalizer(new ClassMetadataFactory($emptyChain))],
            [new JsonEncoder()]
        );

        $error = new Error('Bad request', 'Something is invalid', 400);
        $emptyPayload = $serializerEmpty->normalize($error, 'json', [
            'groups' => ['jsonproblem'],
            'skip_null_values' => true,
            'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
        ]);

        // Bug: without an attribute loader handling these classes, no property carries a group → empty.
        $this->assertSame([], $emptyPayload, 'Sanity check: when no loader handles the Error class, normalization yields an empty payload.');

        // Build a loader chain that hardcodes api-platform's error classes — equivalent to what the
        // compiler pass programmatically registers in the DI container at runtime.
        $errorMappedClasses = [Error::class => [Error::class], ValidationException::class => [ValidationException::class]];
        $errorLoader = new AttributeLoader(allowAnyClass: true, mappedClasses: $errorMappedClasses);

        $chain = new LoaderChain([$disabledAttributeLoader, $errorLoader]);
        $serializer = new Serializer(
            [new ObjectNormalizer(new ClassMetadataFactory($chain))],
            [new JsonEncoder()]
        );

        $payload = $serializer->normalize($error, 'json', [
            'groups' => ['jsonproblem'],
            'skip_null_values' => true,
            'ignored_attributes' => ['trace', 'file', 'line', 'code', 'message', 'traceAsString', 'previous'],
        ]);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('title', $payload);
        $this->assertArrayHasKey('detail', $payload);
        $this->assertArrayHasKey('status', $payload);
        $this->assertSame('Bad request', $payload['title']);
        $this->assertSame('Something is invalid', $payload['detail']);
        $this->assertSame(400, $payload['status']);
    }

    public function testTheCompilerPassDefinitionMatchesTheRuntimeExpectation(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('serializer.mapping.chain_loader', new Definition(LoaderChain::class, [[]]));
        $container->setDefinition('serializer.mapping.cache_warmer', new Definition(\stdClass::class, [[]]));

        (new ErrorResourceAttributeLoaderPass())->process($container);

        $loaderDefinition = $container->getDefinition('serializer.mapping.chain_loader')->getArgument(0)[0];
        /** @var AttributeLoader $loader */
        $loader = new ($loaderDefinition->getClass())(...$loaderDefinition->getArguments());

        // Confirm that running the very definition the compiler pass produces actually loads metadata
        // for the api-platform error class, even with allowAnyClass-style mapped lookups.
        $classMetadata = $this->createMock(ClassMetadataInterface::class);
        $classMetadata->method('getName')->willReturn(Error::class);
        $classMetadata->method('getReflectionClass')->willReturn(new \ReflectionClass(Error::class));
        $classMetadata->method('getAttributesMetadata')->willReturn([]);
        $classMetadata->expects($this->atLeastOnce())->method('addAttributeMetadata');

        $this->assertTrue($loader->loadClassMetadata($classMetadata));
    }
}
