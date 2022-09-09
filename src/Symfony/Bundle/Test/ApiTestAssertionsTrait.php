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

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\JsonSchema\SchemaFactoryInterface as LegacySchemaFactoryInterface;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Symfony\Bundle\Test\Constraint\ArraySubset;
use ApiPlatform\Symfony\Bundle\Test\Constraint\MatchesJsonSchema;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bundle\FrameworkBundle\Test\BrowserKitAssertionsTrait;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
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
     * @param array|string $subset
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public static function assertJsonContains($subset, bool $checkForObjectIdentity = true, string $message = ''): void
    {
        if (\is_string($subset)) {
            $subset = json_decode($subset, true);
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
     *
     * @param array|string $json
     */
    public static function assertJsonEquals($json, string $message = ''): void
    {
        if (\is_string($json)) {
            $json = json_decode($json, true);
        }
        if (!\is_array($json)) {
            throw new \InvalidArgumentException('$json must be array or string (JSON array or JSON object)');
        }

        static::assertEqualsCanonicalizing($json, self::getHttpResponse()->toArray(false), $message);
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
     * @param iterable $subset
     * @param iterable $array
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \Exception
     */
    public static function assertArraySubset($subset, $array, bool $checkForObjectIdentity = false, string $message = ''): void
    {
        $constraint = new ArraySubset($subset, $checkForObjectIdentity);

        static::assertThat($array, $constraint, $message);
    }

    /**
     * @param object|array|string $jsonSchema
     */
    public static function assertMatchesJsonSchema($jsonSchema, ?int $checkMode = null, string $message = ''): void
    {
        $constraint = new MatchesJsonSchema($jsonSchema, $checkMode);

        static::assertThat(self::getHttpResponse()->toArray(false), $constraint, $message);
    }

    public static function assertMatchesResourceCollectionJsonSchema(string $resourceClass, ?string $operationName = null, string $format = 'jsonld'): void
    {
        $schemaFactory = self::getSchemaFactory();

        if ($schemaFactory instanceof LegacySchemaFactoryInterface) {
            $schema = $schemaFactory->buildSchema($resourceClass, $format, Schema::TYPE_OUTPUT, OperationType::COLLECTION, $operationName, null);
        } else {
            if ($resourceMetadataFactoryCollection = self::getResourceMetadataCollectionFactory()) {
                $operation = $resourceMetadataFactoryCollection->create($resourceClass)->getOperation($operationName, true);
            } else {
                $operation = $operationName ? (new GetCollection())->withName($operationName) : new GetCollection();
            }

            $schema = $schemaFactory->buildSchema($resourceClass, $format, Schema::TYPE_OUTPUT, $operation, null);
        }

        static::assertMatchesJsonSchema($schema->getArrayCopy());
    }

    public static function assertMatchesResourceItemJsonSchema(string $resourceClass, ?string $operationName = null, string $format = 'jsonld'): void
    {
        $schemaFactory = self::getSchemaFactory();

        if ($schemaFactory instanceof LegacySchemaFactoryInterface) {
            $schema = $schemaFactory->buildSchema($resourceClass, $format, Schema::TYPE_OUTPUT, OperationType::ITEM, $operationName, null);
        } else {
            if ($resourceMetadataFactoryCollection = self::getResourceMetadataCollectionFactory()) {
                $operation = $resourceMetadataFactoryCollection->create($resourceClass)->getOperation($operationName);
            } else {
                $operation = $operationName ? (new Get())->withName($operationName) : new Get();
            }

            $schema = $schemaFactory->buildSchema($resourceClass, $format, Schema::TYPE_OUTPUT, $operation, null);
        }

        static::assertMatchesJsonSchema($schema->getArrayCopy());
    }

    private static function getHttpClient(Client $newClient = null): ?Client
    {
        static $client;

        if (0 < \func_num_args()) {
            return $client = $newClient;
        }

        if (!$client instanceof Client) {
            static::fail(sprintf('A client must be set to make assertions on it. Did you forget to call "%s::createClient()"?', __CLASS__));
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

    /**
     * @return SchemaFactoryInterface|LegacySchemaFactoryInterface
     */
    private static function getSchemaFactory()
    {
        $container = method_exists(static::class, 'getContainer') ? static::getContainer() : static::$container; // @phpstan-ignore-line

        try {
            /** @var SchemaFactoryInterface|LegacySchemaFactoryInterface $schemaFactory */
            $schemaFactory = $container->get('api_platform.json_schema.schema_factory');
        } catch (ServiceNotFoundException $e) {
            throw new \LogicException('You cannot use the resource JSON Schema assertions if the "api_platform.swagger.versions" config is null or empty.');
        }

        return $schemaFactory;
    }

    /**
     * @return ResourceMetadataCollectionFactoryInterface|null
     */
    private static function getResourceMetadataCollectionFactory()
    {
        $container = method_exists(static::class, 'getContainer') ? static::getContainer() : static::$container; // @phpstan-ignore-line

        try {
            $resourceMetadataFactoryCollection = $container->get('api_platform.metadata.resource.metadata_collection_factory');
        } catch (ServiceNotFoundException $e) {
            return null;
        }

        return $resourceMetadataFactoryCollection;
    }
}

class_alias(ApiTestAssertionsTrait::class, \ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestAssertionsTrait::class);
