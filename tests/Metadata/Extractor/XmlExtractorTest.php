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
use ApiPlatform\Metadata\Extractor\XmlExtractor;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Comment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class XmlExtractorTest extends TestCase
{
    public function testValidXML(): void
    {
        $extractor = new XmlExtractor([__DIR__.'/xml/valid.xml']);
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
                    'compositeIdentifiers' => null,
                    'queryParameterValidationEnabled' => null,
                    'input' => null,
                    'output' => null,
                    'types' => null,
                    'formats' => null,
                    'identifiers' => null,
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
                    'hydraContext' => null,
                    'openapiContext' => null,
                    'validationContext' => null,
                    'filters' => null,
                    'mercure' => null,
                    'messenger' => null,
                    'order' => null,
                    'paginationViaCursor' => null,
                    'exceptionToStatus' => null,
                    'extraProperties' => null,
                    'properties' => null,
                    'operations' => null,
                    'graphQlOperations' => null,
                ],
                [
                    'uriTemplate' => '/users/{author}/comments.{_format}',
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
                    'compositeIdentifiers' => null,
                    'queryParameterValidationEnabled' => null,
                    'input' => null,
                    'output' => null,
                    'types' => ['someirischema', 'anotheririschema'],
                    'formats' => ['jsonld', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                    'identifiers' => [
                        'author' => ['author', Comment::class],
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
                    ],
                    'denormalizationContext' => [
                        'groups' => 'comment:write',
                    ],
                    'hydraContext' => [
                        'foo' => ['bar' => 'baz'],
                    ],
                    'openapiContext' => null,
                    'validationContext' => null,
                    'filters' => ['comment.custom_filter'],
                    'mercure' => ['private' => 'true'],
                    'messenger' => 'input',
                    'order' => ['foo', 'bar'],
                    'paginationViaCursor' => [
                        ['field' => 'id', 'direction' => 'DESC'],
                    ],
                    'exceptionToStatus' => [
                        ExceptionInterface::class => 400,
                    ],
                    'extraProperties' => null,
                    'properties' => null,
                    'operations' => [
                        [
                            'name' => 'custom_operation_name',
                            'class' => GetCollection::class,
                            'uriTemplate' => '/users/{author}/comments.{_format}',
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
                            'compositeIdentifiers' => null,
                            'queryParameterValidationEnabled' => null,
                            'input' => null,
                            'output' => null,
                            'types' => ['someirischema', 'anotheririschema'],
                            'formats' => ['jsonld', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                            'identifiers' => [
                                'author' => ['author', Comment::class],
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
                            ],
                            'denormalizationContext' => [
                                'groups' => 'comment:write',
                            ],
                            'hydraContext' => [
                                'foo' => ['bar' => 'baz'],
                            ],
                            'openapiContext' => null,
                            'validationContext' => null,
                            'filters' => ['comment.custom_filter'],
                            'mercure' => ['private' => 'true'],
                            'messenger' => 'input',
                            'order' => ['foo', 'bar'],
                            'paginationViaCursor' => [
                                ['field' => 'id', 'direction' => 'DESC'],
                            ],
                            'exceptionToStatus' => [
                                ExceptionInterface::class => 400,
                            ],
                            'extraProperties' => null,
                            'properties' => null,
                            'read' => null,
                            'deserialize' => null,
                            'validate' => null,
                            'write' => null,
                            'serialize' => null,
                            'queryParameterValidate' => null,
                            'priority' => null,
                        ],
                        [
                            'name' => null,
                            'class' => Get::class,
                            'uriTemplate' => '/users/{userId}/comments/{id}.{_format}',
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
                            'compositeIdentifiers' => null,
                            'queryParameterValidationEnabled' => null,
                            'input' => null,
                            'output' => null,
                            'types' => ['someirischema', 'anotheririschema'],
                            'formats' => ['jsonld', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                            'identifiers' => [
                                'userId' => ['author', Comment::class],
                                'id' => ['id', Comment::class],
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
                            ],
                            'denormalizationContext' => [
                                'groups' => 'comment:write',
                            ],
                            'hydraContext' => [
                                'foo' => ['bar' => 'baz'],
                            ],
                            'openapiContext' => null,
                            'validationContext' => null,
                            'filters' => ['comment.custom_filter'],
                            'mercure' => ['private' => 'true'],
                            'messenger' => 'input',
                            'order' => ['foo', 'bar'],
                            'paginationViaCursor' => [
                                ['field' => 'id', 'direction' => 'DESC'],
                            ],
                            'exceptionToStatus' => [
                                ExceptionInterface::class => 400,
                            ],
                            'extraProperties' => [
                                'foo' => 'bar',
                            ],
                            'properties' => null,
                            'read' => null,
                            'deserialize' => null,
                            'validate' => null,
                            'write' => null,
                            'serialize' => null,
                            'queryParameterValidate' => null,
                            'priority' => null,
                        ],
                    ],
                    'graphQlOperations' => null,
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
        $this->expectExceptionMessage($error);

        $extractor = new XmlExtractor([$path]);
        $extractor->getResources();
    }

    public function getInvalidPaths(): array
    {
        return [
            [__DIR__.'/xml/invalid/required_class.xml', "[ERROR 1868] Element '{https://api-platform.com/schema/metadata-3.0}resource': The attribute 'class' is required but missing. (in ".realpath(__DIR__.'/../../../').'/ - line 7, column 0)'],
        ];
    }
}
