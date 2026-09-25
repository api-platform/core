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

namespace ApiPlatform\Metadata\Tests\Extractor;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Extractor\XmlResourceExtractor;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\ExtractorResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Comment;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Program;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\User;
use ApiPlatform\Test\ComparableObjectTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class XmlExtractorTest extends TestCase
{
    use ComparableObjectTrait;

    public function testValidXML(): void
    {
        $extractor = new XmlResourceExtractor([__DIR__.'/xml/valid.xml']);
        $this->assertSame(self::toComparableArray([
            Comment::class => [
                [
                    'shortName' => null,
                    'description' => null,
                    'urlGenerationStrategy' => null,
                    'deprecationReason' => null,
                    'messenger' => null,
                    'mercure' => null,
                    'input' => null,
                    'output' => null,
                    'fetchPartial' => null,
                    'forceEager' => null,
                    'paginationClientEnabled' => null,
                    'paginationClientItemsPerPage' => null,
                    'paginationClientPartial' => null,
                    'paginationEnabled' => null,
                    'paginationFetchJoinCollection' => null,
                    'paginationUseOutputWalkers' => null,
                    'paginationItemsPerPage' => null,
                    'paginationMaximumItemsPerPage' => null,
                    'paginationPartial' => null,
                    'paginationType' => null,
                    'processor' => null,
                    'provider' => null,
                    'security' => null,
                    'securityMessage' => null,
                    'securityPostDenormalize' => null,
                    'securityPostDenormalizeMessage' => null,
                    'securityPostValidation' => null,
                    'securityPostValidationMessage' => null,
                    'normalizationContext' => null,
                    'denormalizationContext' => null,
                    'collectDenormalizationErrors' => null,
                    'validationContext' => null,
                    'filters' => null,
                    'order' => null,
                    'extraProperties' => null,
                    'read' => null,
                    'write' => null,
                    'jsonStream' => null,
                    'map' => null,

                    'throwOnNotFound' => null,
                    'uriTemplate' => null,
                    'routePrefix' => null,
                    'stateless' => null,
                    'sunset' => null,
                    'acceptPatch' => null,
                    'status' => null,
                    'host' => null,
                    'condition' => null,
                    'controller' => null,
                    'types' => null,
                    'formats' => null,
                    'inputFormats' => null,
                    'outputFormats' => null,
                    'uriVariables' => null,
                    'defaults' => null,
                    'requirements' => null,
                    'options' => null,
                    'schemes' => null,
                    'cacheHeaders' => null,
                    'hydraContext' => null,
                    'jsonldContext' => null,
                    'openapi' => null,
                    'paginationViaCursor' => null,
                    'exceptionToStatus' => null,
                    'queryParameterValidationEnabled' => null,
                    'strictQueryParameterValidation' => null,
                    'hideHydraOperation' => null,
                    'stateOptions' => null,
                    'links' => null,
                    'headers' => null,
                    'parameters' => null,
                    'operations' => null,
                    'graphQlOperations' => null, ],
                [
                    'shortName' => null,
                    'description' => 'User comments',
                    'urlGenerationStrategy' => null,
                    'deprecationReason' => null,
                    'messenger' => 'input',
                    'mercure' => ['private' => true],
                    'input' => null,
                    'output' => null,
                    'fetchPartial' => null,
                    'forceEager' => null,
                    'paginationClientEnabled' => null,
                    'paginationClientItemsPerPage' => null,
                    'paginationClientPartial' => null,
                    'paginationEnabled' => null,
                    'paginationFetchJoinCollection' => null,
                    'paginationUseOutputWalkers' => null,
                    'paginationItemsPerPage' => null,
                    'paginationMaximumItemsPerPage' => null,
                    'paginationPartial' => null,
                    'paginationType' => null,
                    'processor' => null,
                    'provider' => null,
                    'security' => null,
                    'securityMessage' => null,
                    'securityPostDenormalize' => null,
                    'securityPostDenormalizeMessage' => null,
                    'securityPostValidation' => null,
                    'securityPostValidationMessage' => null,
                    'normalizationContext' => [
                        'groups' => ['comment:read', 'comment:custom-read'],
                        'enabled' => true, ],
                    'denormalizationContext' => [
                        'groups' => 'comment:write', ],
                    'collectDenormalizationErrors' => null,
                    'validationContext' => null,
                    'filters' => ['comment.custom_filter'],
                    'order' => ['foo', 'bar'],
                    'extraProperties' => null,
                    'read' => null,
                    'write' => null,
                    'jsonStream' => null,
                    'map' => null,

                    'throwOnNotFound' => null,
                    'uriTemplate' => '/users/{author}/comments{._format}',
                    'routePrefix' => null,
                    'stateless' => null,
                    'sunset' => null,
                    'acceptPatch' => null,
                    'status' => null,
                    'host' => null,
                    'condition' => null,
                    'controller' => null,
                    'types' => ['someirischema', 'anotheririschema'],
                    'formats' => ['jsonld', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                    'inputFormats' => ['json' => 'application/merge-patch+json'],
                    'outputFormats' => ['json' => 'application/merge-patch+json'],
                    'uriVariables' => [
                        'author' => 'author', ],
                    'defaults' => null,
                    'requirements' => null,
                    'options' => null,
                    'schemes' => null,
                    'cacheHeaders' => [
                        'max_age' => 60,
                        'shared_max_age' => 120,
                        'vary' => ['Authorization', 'Accept-Language'], ],
                    'hydraContext' => [
                        'foo' => ['bar' => 'baz'], ],
                    'jsonldContext' => null,
                    'openapi' => null,
                    'paginationViaCursor' => [
                        'id' => 'DESC', ],
                    'exceptionToStatus' => [
                        ExceptionInterface::class => 400, ],
                    'queryParameterValidationEnabled' => null,
                    'strictQueryParameterValidation' => null,
                    'hideHydraOperation' => null,
                    'stateOptions' => null,
                    'links' => null,
                    'headers' => ['hello' => 'world'],
                    'parameters' => null,
                    'operations' => [
                        [
                            'shortName' => null,
                            'description' => 'User comments',
                            'urlGenerationStrategy' => null,
                            'deprecationReason' => null,
                            'messenger' => 'input',
                            'mercure' => ['private' => true],
                            'input' => null,
                            'output' => null,
                            'fetchPartial' => null,
                            'forceEager' => null,
                            'paginationClientEnabled' => null,
                            'paginationClientItemsPerPage' => null,
                            'paginationClientPartial' => null,
                            'paginationEnabled' => null,
                            'paginationFetchJoinCollection' => null,
                            'paginationUseOutputWalkers' => null,
                            'paginationItemsPerPage' => null,
                            'paginationMaximumItemsPerPage' => null,
                            'paginationPartial' => null,
                            'paginationType' => null,
                            'processor' => null,
                            'provider' => null,
                            'security' => null,
                            'securityMessage' => null,
                            'securityPostDenormalize' => null,
                            'securityPostDenormalizeMessage' => null,
                            'securityPostValidation' => null,
                            'securityPostValidationMessage' => null,
                            'normalizationContext' => [
                                'groups' => ['comment:read', 'comment:custom-read'],
                                'enabled' => true, ],
                            'denormalizationContext' => [
                                'groups' => 'comment:write', ],
                            'collectDenormalizationErrors' => null,
                            'validationContext' => null,
                            'filters' => ['comment.custom_filter'],
                            'order' => ['foo', 'bar'],
                            'extraProperties' => null,
                            'read' => null,
                            'write' => null,
                            'jsonStream' => null,
                            'map' => null,

                            'throwOnNotFound' => null,
                            'uriTemplate' => '/users/{author}/comments{._format}',
                            'routePrefix' => null,
                            'stateless' => null,
                            'sunset' => null,
                            'acceptPatch' => null,
                            'status' => null,
                            'host' => null,
                            'condition' => null,
                            'controller' => null,
                            'types' => ['someirischema', 'anotheririschema'],
                            'formats' => ['jsonld', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                            'inputFormats' => ['json' => 'application/merge-patch+json'],
                            'outputFormats' => ['json' => 'application/merge-patch+json'],
                            'uriVariables' => [
                                'author' => 'author', ],
                            'defaults' => null,
                            'requirements' => null,
                            'options' => null,
                            'schemes' => null,
                            'cacheHeaders' => [
                                'max_age' => 60,
                                'shared_max_age' => 120,
                                'vary' => ['Authorization', 'Accept-Language'], ],
                            'hydraContext' => [
                                'foo' => ['bar' => 'baz'], ],
                            'jsonldContext' => null,
                            'openapi' => null,
                            'paginationViaCursor' => [
                                'id' => 'DESC', ],
                            'exceptionToStatus' => [
                                ExceptionInterface::class => 400, ],
                            'queryParameterValidationEnabled' => null,
                            'strictQueryParameterValidation' => null,
                            'hideHydraOperation' => null,
                            'stateOptions' => null,
                            'links' => null,
                            'headers' => ['hello' => 'world'],
                            'parameters' => null,
                            'itemUriTemplate' => null,
                            'collection' => null,
                            'class' => GetCollection::class,
                            'method' => null,
                            'deserialize' => null,
                            'validate' => null,
                            'serialize' => null,
                            'queryParameterValidate' => null,
                            'priority' => null,
                            'routePriority' => null,
                            'name' => 'custom_operation_name',
                            'routeName' => 'custom_route_name', ],
                        [
                            'shortName' => null,
                            'description' => 'User comments',
                            'urlGenerationStrategy' => null,
                            'deprecationReason' => null,
                            'messenger' => 'input',
                            'mercure' => ['private' => true],
                            'input' => null,
                            'output' => null,
                            'fetchPartial' => null,
                            'forceEager' => null,
                            'paginationClientEnabled' => null,
                            'paginationClientItemsPerPage' => null,
                            'paginationClientPartial' => null,
                            'paginationEnabled' => null,
                            'paginationFetchJoinCollection' => null,
                            'paginationUseOutputWalkers' => null,
                            'paginationItemsPerPage' => null,
                            'paginationMaximumItemsPerPage' => null,
                            'paginationPartial' => null,
                            'paginationType' => null,
                            'processor' => null,
                            'provider' => null,
                            'security' => null,
                            'securityMessage' => null,
                            'securityPostDenormalize' => null,
                            'securityPostDenormalizeMessage' => null,
                            'securityPostValidation' => null,
                            'securityPostValidationMessage' => null,
                            'normalizationContext' => [
                                'groups' => ['comment:read', 'comment:custom-read'],
                                'enabled' => true, ],
                            'denormalizationContext' => [
                                'groups' => 'comment:write', ],
                            'collectDenormalizationErrors' => null,
                            'validationContext' => null,
                            'filters' => ['comment.custom_filter'],
                            'order' => ['foo', 'bar'],
                            'extraProperties' => [
                                'foo' => 'bar',
                                'boolean' => true, ],
                            'read' => null,
                            'write' => null,
                            'jsonStream' => null,
                            'map' => null,

                            'throwOnNotFound' => null,
                            'uriTemplate' => '/users/{userId}/comments/{id}{._format}',
                            'routePrefix' => null,
                            'stateless' => null,
                            'sunset' => null,
                            'acceptPatch' => null,
                            'status' => null,
                            'host' => null,
                            'condition' => null,
                            'controller' => null,
                            'types' => ['someirischema', 'anotheririschema'],
                            'formats' => ['jsonld', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                            'inputFormats' => ['json' => 'application/merge-patch+json'],
                            'outputFormats' => ['json' => 'application/merge-patch+json'],
                            'uriVariables' => [
                                'userId' => [
                                    'from_property' => 'author',
                                    'from_class' => User::class, ],
                                'id' => 'id', ],
                            'defaults' => null,
                            'requirements' => null,
                            'options' => null,
                            'schemes' => null,
                            'cacheHeaders' => [
                                'max_age' => 60,
                                'shared_max_age' => 120,
                                'vary' => ['Authorization', 'Accept-Language'], ],
                            'hydraContext' => [
                                'foo' => ['bar' => 'baz'], ],
                            'jsonldContext' => null,
                            'openapi' => null,
                            'paginationViaCursor' => [
                                'id' => 'DESC', ],
                            'exceptionToStatus' => [
                                ExceptionInterface::class => 400, ],
                            'queryParameterValidationEnabled' => null,
                            'strictQueryParameterValidation' => null,
                            'hideHydraOperation' => null,
                            'stateOptions' => null,
                            'links' => null,
                            'headers' => ['hello' => 'world'],
                            'parameters' => [
                                'author' => new QueryParameter(
                                    key: 'author',
                                    required: true,
                                    schema: [
                                        'type' => 'string',
                                    ],
                                    extraProperties: ['foo' => 'bar']
                                ), ],
                            'collection' => null,
                            'class' => Get::class,
                            'method' => null,
                            'deserialize' => null,
                            'validate' => null,
                            'serialize' => null,
                            'queryParameterValidate' => null,
                            'priority' => null,
                            'routePriority' => null,
                            'name' => null,
                            'routeName' => null, ], ],
                    'graphQlOperations' => null, ], ],
        ]), self::toComparableArray($extractor->getResources()));
    }

    public function testContainerParametersAreResolved(): void
    {
        $parameters = [
            'user.class' => User::class,
            'user.route_prefix' => '/admin',
            'user.security' => 'is_granted("ROLE_ADMIN")',
        ];
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(static fn (string $id): string => $parameters[$id]);

        $extractor = new XmlResourceExtractor([__DIR__.'/xml/parameters.xml'], $container);
        $resources = $extractor->getResources();

        $this->assertArrayHasKey(User::class, $resources);

        // scalar string field: %param% resolved anywhere in the string
        $this->assertSame('/admin', $resources[User::class][0]['routePrefix']);

        // expression field, whole-string %param%: resolved to the stored expression so it reaches
        // ExpressionLanguage (the literal #8104 case, which throws if left as "%user.security%")
        $this->assertSame('is_granted("ROLE_ADMIN")', $resources[User::class][0]['security']);

        // expression field with a real expression (no whole-string param): left untouched
        $this->assertSame('is_granted("ROLE_USER")', $resources[User::class][0]['operations'][0]['security']);
    }

    #[DataProvider('getInvalidPaths')]
    public function testInvalidXML(string $path, string $error): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($error);

        (new XmlResourceExtractor([$path]))->getResources();
    }

    public static function getInvalidPaths(): array
    {
        return [
            [
                __DIR__.'/xml/invalid/required_class.xml',
                "/^Error while parsing .+\/xml\/invalid\/required_class.xml: \[ERROR 1868\] Element '\{https:\/\/api-platform\.com\/schema\/metadata\/resources-3\.0\}resource': The attribute 'class' is required but missing\./",
            ],
        ];
    }

    /**
     * Tests issue #8175: two XML operations sharing an explicit name silently
     * dropped one another. The factory must reject the duplicate up front.
     */
    public function testDuplicateOperationNameFromXmlThrows(): void
    {
        $extractor = new XmlResourceExtractor([__DIR__.'/xml/duplicate_operation_name.xml']);
        $factory = new ExtractorResourceMetadataCollectionFactory($extractor);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/_api_\/forms\/\{id\}\/submit\{\._format\}/');

        $factory->create(Comment::class);
    }

    public function testOpenApiParametersAreAList(): void
    {
        $extractor = new XmlResourceExtractor([__DIR__.'/xml/openapi_parameters.xml']);
        $resources = $extractor->getResources();

        $operation = $resources[Program::class][0]['operations'][0]['openapi'];
        $parameters = $operation->getParameters();

        $this->assertTrue(array_is_list($parameters));
        $this->assertEquals('author', $parameters[0]->getName());
        $this->assertEquals('path', $parameters[0]->getIn());
        $this->assertEquals('john-doe', $parameters[0]->getExample());
        $this->assertEquals('format', $parameters[1]->getName());
    }
}
