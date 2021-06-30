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

namespace ApiPlatform\Tests\Metadata\Extractor;

use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Extractor\XmlResourceExtractor;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Comment;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class XmlExtractorTest extends TestCase
{
    public function testValidXML(): void
    {
        $extractor = new XmlResourceExtractor([__DIR__.'/xml/valid.xml']);
        $this->assertEquals([
            Comment::class => [
                [
                    'uriTemplate' => null,
                    'shortName' => null,
                    'description' => null,
                    'routePrefix' => null,
                    'stateless' => null,
                    'sunset' => null,
                    'acceptPatch' => null,
                    'host' => null,
                    'condition' => null,
                    'controller' => null,
                    'urlGenerationStrategy' => null,
                    'deprecationReason' => null,
                    'elasticsearch' => null,
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
                    'security' => null,
                    'securityMessage' => null,
                    'securityPostDenormalize' => null,
                    'securityPostDenormalizeMessage' => null,
                    'securityPostValidation' => null,
                    'securityPostValidationMessage' => null,
                    'queryParameterValidationEnabled' => null,
                    'input' => null,
                    'output' => null,
                    'types' => null,
                    'formats' => null,
                    'uriVariables' => null,
                    'inputFormats' => null,
                    'outputFormats' => null,
                    'defaults' => null,
                    'requirements' => null,
                    'options' => null,
                    'status' => null,
                    'schemes' => null,
                    'cacheHeaders' => null,
                    'normalizationContext' => null,
                    'denormalizationContext' => null,
                    'collectDenormalizationErrors' => null,
                    'hydraContext' => null,
                    'openapiContext' => null,
                    'openapi' => null,
                    'validationContext' => null,
                    'translation' => null,
                    'filters' => null,
                    'mercure' => null,
                    'messenger' => null,
                    'order' => null,
                    'paginationViaCursor' => null,
                    'exceptionToStatus' => null,
                    'extraProperties' => null,
                    'operations' => null,
                    'graphQlOperations' => null,
                    'class' => Comment::class,
                    'processor' => null,
                    'provider' => null,
                    'read' => null,
                    'write' => null,
                    'stateOptions' => null,
                ],
                [
                    'uriTemplate' => '/users/{author}/comments{._format}',
                    'shortName' => null,
                    'description' => 'User comments',
                    'routePrefix' => null,
                    'stateless' => null,
                    'sunset' => null,
                    'acceptPatch' => null,
                    'host' => null,
                    'condition' => null,
                    'controller' => null,
                    'urlGenerationStrategy' => null,
                    'deprecationReason' => null,
                    'elasticsearch' => null,
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
                    'security' => null,
                    'securityMessage' => null,
                    'securityPostDenormalize' => null,
                    'securityPostDenormalizeMessage' => null,
                    'securityPostValidation' => null,
                    'securityPostValidationMessage' => null,
                    'queryParameterValidationEnabled' => null,
                    'input' => null,
                    'output' => null,
                    'types' => ['someirischema', 'anotheririschema'],
                    'formats' => ['jsonld', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                    'uriVariables' => [
                        'author' => 'author',
                    ],
                    'inputFormats' => ['json' => 'application/merge-patch+json'],
                    'outputFormats' => ['json' => 'application/merge-patch+json'],
                    'defaults' => null,
                    'requirements' => null,
                    'options' => null,
                    'status' => null,
                    'schemes' => null,
                    'cacheHeaders' => [
                        'max_age' => 60,
                        'shared_max_age' => 120,
                        'vary' => ['Authorization', 'Accept-Language'],
                    ],
                    'normalizationContext' => [
                        'groups' => ['comment:read', 'comment:custom-read'],
                        'enabled' => true,
                    ],
                    'denormalizationContext' => [
                        'groups' => 'comment:write',
                    ],
                    'collectDenormalizationErrors' => null,
                    'hydraContext' => [
                        'foo' => ['bar' => 'baz'],
                    ],
                    'openapiContext' => null,
                    'openapi' => null,
                    'validationContext' => null,
                    'translation' => null,
                    'filters' => ['comment.custom_filter'],
                    'mercure' => ['private' => true],
                    'messenger' => 'input',
                    'order' => ['foo', 'bar'],
                    'paginationViaCursor' => [
                        'id' => 'DESC',
                    ],
                    'exceptionToStatus' => [
                        ExceptionInterface::class => 400,
                    ],
                    'extraProperties' => null,
                    'operations' => [
                        [
                            'name' => 'custom_operation_name',
                            'class' => GetCollection::class,
                            'uriTemplate' => '/users/{author}/comments{._format}',
                            'shortName' => null,
                            'description' => 'User comments',
                            'routePrefix' => null,
                            'stateless' => null,
                            'sunset' => null,
                            'acceptPatch' => null,
                            'host' => null,
                            'condition' => null,
                            'controller' => null,
                            'urlGenerationStrategy' => null,
                            'deprecationReason' => null,
                            'elasticsearch' => null,
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
                            'security' => null,
                            'securityMessage' => null,
                            'securityPostDenormalize' => null,
                            'securityPostDenormalizeMessage' => null,
                            'securityPostValidation' => null,
                            'securityPostValidationMessage' => null,
                            'queryParameterValidationEnabled' => null,
                            'input' => null,
                            'output' => null,
                            'types' => ['someirischema', 'anotheririschema'],
                            'formats' => ['jsonld', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                            'uriVariables' => [
                                'author' => 'author',
                            ],
                            'inputFormats' => ['json' => 'application/merge-patch+json'],
                            'outputFormats' => ['json' => 'application/merge-patch+json'],
                            'defaults' => null,
                            'requirements' => null,
                            'options' => null,
                            'status' => null,
                            'schemes' => null,
                            'cacheHeaders' => [
                                'max_age' => 60,
                                'shared_max_age' => 120,
                                'vary' => ['Authorization', 'Accept-Language'],
                            ],
                            'normalizationContext' => [
                                'groups' => ['comment:read', 'comment:custom-read'],
                                'enabled' => true,
                            ],
                            'denormalizationContext' => [
                                'groups' => 'comment:write',
                            ],
                            'collectDenormalizationErrors' => null,
                            'hydraContext' => [
                                'foo' => ['bar' => 'baz'],
                            ],
                            'openapiContext' => null,
                            'openapi' => null,
                            'validationContext' => null,
                            'translation' => null,
                            'filters' => ['comment.custom_filter'],
                            'mercure' => ['private' => true],
                            'messenger' => 'input',
                            'order' => ['foo', 'bar'],
                            'paginationViaCursor' => [
                                'id' => 'DESC',
                            ],
                            'exceptionToStatus' => [
                                ExceptionInterface::class => 400,
                            ],
                            'extraProperties' => null,
                            'read' => null,
                            'deserialize' => null,
                            'validate' => null,
                            'write' => null,
                            'serialize' => null,
                            'queryParameterValidate' => null,
                            'collection' => null,
                            'method' => null,
                            'priority' => null,
                            'processor' => null,
                            'provider' => null,
                            'itemUriTemplate' => null,
                            'stateOptions' => null,
                        ],
                        [
                            'name' => null,
                            'class' => Get::class,
                            'uriTemplate' => '/users/{userId}/comments/{id}{._format}',
                            'shortName' => null,
                            'description' => 'User comments',
                            'routePrefix' => null,
                            'stateless' => null,
                            'sunset' => null,
                            'acceptPatch' => null,
                            'host' => null,
                            'condition' => null,
                            'controller' => null,
                            'urlGenerationStrategy' => null,
                            'deprecationReason' => null,
                            'elasticsearch' => null,
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
                            'security' => null,
                            'securityMessage' => null,
                            'securityPostDenormalize' => null,
                            'securityPostDenormalizeMessage' => null,
                            'securityPostValidation' => null,
                            'securityPostValidationMessage' => null,
                            'queryParameterValidationEnabled' => null,
                            'input' => null,
                            'output' => null,
                            'types' => ['someirischema', 'anotheririschema'],
                            'formats' => ['jsonld', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                            'uriVariables' => [
                                'userId' => [
                                    'from_property' => 'author',
                                    'from_class' => User::class,
                                ],
                                'id' => 'id',
                            ],
                            'inputFormats' => ['json' => 'application/merge-patch+json'],
                            'outputFormats' => ['json' => 'application/merge-patch+json'],
                            'defaults' => null,
                            'requirements' => null,
                            'options' => null,
                            'status' => null,
                            'schemes' => null,
                            'cacheHeaders' => [
                                'max_age' => 60,
                                'shared_max_age' => 120,
                                'vary' => ['Authorization', 'Accept-Language'],
                            ],
                            'normalizationContext' => [
                                'groups' => ['comment:read', 'comment:custom-read'],
                                'enabled' => true,
                            ],
                            'denormalizationContext' => [
                                'groups' => 'comment:write',
                            ],
                            'collectDenormalizationErrors' => null,
                            'hydraContext' => [
                                'foo' => ['bar' => 'baz'],
                            ],
                            'openapiContext' => null,
                            'openapi' => null,
                            'validationContext' => null,
                            'translation' => null,
                            'filters' => ['comment.custom_filter'],
                            'mercure' => ['private' => true],
                            'messenger' => 'input',
                            'order' => ['foo', 'bar'],
                            'paginationViaCursor' => [
                                'id' => 'DESC',
                            ],
                            'exceptionToStatus' => [
                                ExceptionInterface::class => 400,
                            ],
                            'extraProperties' => [
                                'foo' => 'bar',
                            ],
                            'read' => null,
                            'deserialize' => null,
                            'validate' => null,
                            'write' => null,
                            'serialize' => null,
                            'queryParameterValidate' => null,
                            'collection' => null,
                            'method' => null,
                            'priority' => null,
                            'processor' => null,
                            'provider' => null,
                            'stateOptions' => null,
                        ],
                    ],
                    'graphQlOperations' => null,
                    'class' => Comment::class,
                    'processor' => null,
                    'provider' => null,
                    'read' => null,
                    'write' => null,
                    'stateOptions' => null,
                ],
            ],
        ], $extractor->getResources());
    }

    /**
     * @dataProvider getInvalidPaths
     */
    public function testInvalidXML(string $path, string $error): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches($error);

        (new XmlResourceExtractor([$path]))->getResources();
    }

    public function getInvalidPaths(): array
    {
        return [
            [
                __DIR__.'/xml/invalid/required_class.xml',
                "/^Error while parsing .+\/xml\/invalid\/required_class.xml: \[ERROR 1868\] Element '\{https:\/\/api-platform\.com\/schema\/metadata\/resources-3\.0\}resource': The attribute 'class' is required but missing\./",
            ],
        ];
    }
}
