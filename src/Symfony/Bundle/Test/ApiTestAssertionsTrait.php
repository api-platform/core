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

namespace ApiPlatform\Symfony\Bundle\Test;

use ApiPlatform\JsonSchema\BackwardCompatibleSchemaFactory;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Symfony\Bundle\Test\Constraint\ArraySubset;
use ApiPlatform\Symfony\Bundle\Test\Constraint\MatchesJsonSchema;
use PHPUnit\Framework\Constraint\JsonMatches;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bundle\FrameworkBundle\Test\BrowserKitAssertionsTrait;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Mercure\Debug\TraceableHub;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @see \Symfony\Bundle\FrameworkBundle\Test\WebTestAssertionsTrait
 */
trait ApiTestAssertionsTrait
{
    use BrowserKitAssertionsTrait;

    /**
     * Asserts that the retrieved JSON contains the specified subset.
     *
     * This method delegates to static::assertArraySubset().
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public static function assertJsonContains(array|string $subset, bool $checkForObjectIdentity = true, string $message = ''): void
    {
        if (\is_string($subset)) {
            $subset = json_decode($subset, true, 512, \JSON_THROW_ON_ERROR);
        }
        if (!\is_array($subset)) {
            throw new \InvalidArgumentException('$subset must be array or string (JSON array or JSON object)');
        }

        static::assertArraySubset($subset, self::getHttpResponse()->toArray(false), $checkForObjectIdentity, $message);
    }

    /**
     * Asserts that the retrieved JSON is equal to $json.
     *
     * Both values are canonicalized before the comparison.
     */
    public static function assertJsonEquals(array|string $json, string $message = ''): void
    {
        if (\is_array($json)) {
            $json = json_encode(
                $json,
                \JSON_UNESCAPED_UNICODE
                | \JSON_UNESCAPED_SLASHES
                | \JSON_PRESERVE_ZERO_FRACTION
                | \JSON_THROW_ON_ERROR);
        }

        $constraint = new JsonMatches($json);

        static::assertThat(self::getHttpResponse()->getContent(false), $constraint, $message);
    }

    /**
     * Asserts that an array has a specified subset.
     *
     * Imported from dms/phpunit-arraysubset, because the original constraint has been deprecated.
     *
     * @copyright Sebastian Bergmann <sebastian@phpunit.de>
     * @copyright Rafael Dohms <rdohms@gmail.com>
     *
     * @see https://github.com/sebastianbergmann/phpunit/issues/3494
     *
     * @throws ExpectationFailedException
     * @throws \Exception
     */
    public static function assertArraySubset(iterable $subset, iterable $array, bool $checkForObjectIdentity = false, string $message = ''): void
    {
        $constraint = new ArraySubset($subset, $checkForObjectIdentity);

        static::assertThat($array, $constraint, $message);
    }

    public static function assertMatchesJsonSchema(object|array|string $jsonSchema, ?int $checkMode = null, string $message = ''): void
    {
        $constraint = new MatchesJsonSchema($jsonSchema, $checkMode);

        static::assertThat(self::getHttpResponse()->toArray(false), $constraint, $message);
    }

    public static function assertMatchesResourceCollectionJsonSchema(string $resourceClass, ?string $operationName = null, string $format = 'jsonld', ?array $serializationContext = null, ?int $checkMode = null): void
    {
        $schemaFactory = self::getSchemaFactory();

        if ($resourceMetadataFactoryCollection = self::getResourceMetadataCollectionFactory()) {
            $operation = $resourceMetadataFactoryCollection->create($resourceClass)->getOperation($operationName, true);
        } else {
            $operation = $operationName ? (new GetCollection())->withName($operationName) : new GetCollection();
        }

        $serializationContext = $serializationContext ?? $operation->getNormalizationContext();

        $schema = $schemaFactory->buildSchema($resourceClass, $format, Schema::TYPE_OUTPUT, $operation, null, ($serializationContext ?? []) + [BackwardCompatibleSchemaFactory::SCHEMA_DRAFT4_VERSION => true]);

        static::assertMatchesJsonSchema($schema->getArrayCopy(), $checkMode);
    }

    public static function assertMatchesResourceItemJsonSchema(string $resourceClass, ?string $operationName = null, string $format = 'jsonld', ?array $serializationContext = null, ?int $checkMode = null): void
    {
        $schemaFactory = self::getSchemaFactory();

        if ($resourceMetadataFactoryCollection = self::getResourceMetadataCollectionFactory()) {
            $operation = $resourceMetadataFactoryCollection->create($resourceClass)->getOperation($operationName);
        } else {
            $operation = $operationName ? (new Get())->withName($operationName) : new Get();
        }

        $serializationContext = $serializationContext ?? $operation->getNormalizationContext();

        $schema = $schemaFactory->buildSchema($resourceClass, $format, Schema::TYPE_OUTPUT, $operation, null, ($serializationContext ?? []) + [BackwardCompatibleSchemaFactory::SCHEMA_DRAFT4_VERSION => true]);

        static::assertMatchesJsonSchema($schema->getArrayCopy(), $checkMode);
    }

    /**
     * @return Update[]
     */
    public static function getMercureMessages(?string $hubName = null): array
    {
        return array_map(fn (array $update) => $update['object'], self::getMercureHub($hubName)->getMessages());
    }

    public static function getMercureMessage(int $index = 0, ?string $hubName = null): ?Update
    {
        return static::getMercureMessages($hubName)[$index] ?? null;
    }

    /**
     * @throws \JsonException
     */
    public static function assertMercureUpdateMatchesJsonSchema(Update $update, array $topics, array|object|string $jsonSchema = '', bool $private = false, ?string $id = null, ?string $type = null, ?int $retry = null, string $message = ''): void
    {
        static::assertSame($topics, $update->getTopics(), $message);
        static::assertThat(json_decode($update->getData(), true, \JSON_THROW_ON_ERROR), new MatchesJsonSchema($jsonSchema), $message);
        static::assertSame($private, $update->isPrivate(), $message);
        static::assertSame($id, $update->getId(), $message);
        static::assertSame($type, $update->getType(), $message);
        static::assertSame($retry, $update->getRetry(), $message);
    }

    public static function getMercureRegistry(): HubRegistry
    {
        $container = static::getContainer();
        if ($container->has(HubRegistry::class)) {
            return $container->get(HubRegistry::class);
        }

        static::fail('A client must have Mercure enabled to make update assertions. Did you forget to require symfony/mercure?');
    }

    public static function getMercureHub(?string $name = null): TraceableHub
    {
        $hub = self::getMercureRegistry()->getHub($name);
        if (!$hub instanceof TraceableHub) {
            static::fail('You must enable "framework.test" to make Mercure update assertions.');
        }

        return $hub;
    }

    private static function getHttpClient(?Client $newClient = null): ?Client
    {
        static $client;

        if (0 < \func_num_args()) {
            return $client = $newClient;
        }

        if (!$client instanceof Client) {
            static::fail(\sprintf('A client must be set to make assertions on it. Did you forget to call "%s::createClient()"?', self::class));
        }

        return $client;
    }

    private static function getHttpResponse(): ResponseInterface
    {
        if (!$response = self::getHttpClient()->getResponse()) {
            static::fail('A client must have an HTTP Response to make assertions. Did you forget to make an HTTP request?');
        }

        return $response;
    }

    private static function getSchemaFactory(): SchemaFactoryInterface
    {
        $container = static::getContainer();

        try {
            /** @var SchemaFactoryInterface $schemaFactory */
            $schemaFactory = $container->get('api_platform.json_schema.schema_factory');
        } catch (ServiceNotFoundException) {
            throw new \LogicException('You cannot use the resource JSON Schema assertions if the "api_platform.swagger.versions" config is null or empty.');
        }

        return $schemaFactory;
    }

    private static function getResourceMetadataCollectionFactory(): ?ResourceMetadataCollectionFactoryInterface
    {
        $container = static::getContainer();

        try {
            $resourceMetadataFactoryCollection = $container->get('api_platform.metadata.resource.metadata_collection_factory');
        } catch (ServiceNotFoundException) {
            return null;
        }

        return $resourceMetadataFactoryCollection;
    }
}
