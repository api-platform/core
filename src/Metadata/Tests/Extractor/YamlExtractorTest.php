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
use ApiPlatform\Metadata\Extractor\YamlResourceExtractor;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\FlexConfig;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Program;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\SingleFileConfigDummy;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\User;
use PHPUnit\Framework\TestCase;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class YamlExtractorTest extends TestCase
{
    public function testValidYaml(): void
    {
        $extractor = new YamlResourceExtractor([__DIR__.'/yaml/valid.yaml']);
        $this->assertEquals([
            FlexConfig::class => [
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
                    'filters' => null,
                    'mercure' => null,
                    'messenger' => null,
                    'order' => null,
                    'paginationViaCursor' => null,
                    'exceptionToStatus' => null,
                    'extraProperties' => null,
                    'operations' => null,
                    'graphQlOperations' => null,
                    'processor' => null,
                    'provider' => null,
                    'read' => null,
                    'write' => null,
                    'stateOptions' => null,
                    'links' => null,
                ],
            ],
            Program::class => [
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
                    'filters' => null,
                    'mercure' => null,
                    'messenger' => null,
                    'order' => null,
                    'paginationViaCursor' => null,
                    'exceptionToStatus' => null,
                    'extraProperties' => null,
                    'operations' => null,
                    'graphQlOperations' => null,
                    'processor' => null,
                    'provider' => null,
                    'read' => null,
                    'write' => null,
                    'stateOptions' => null,
                    'links' => null,
                ],
                [
                    'uriTemplate' => '/users/{author}/programs{._format}',
                    'shortName' => null,
                    'description' => 'User programs',
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
                    'types' => ['someirischema'],
                    'formats' => null,
                    'uriVariables' => ['author' => 'author'],
                    'inputFormats' => null,
                    'outputFormats' => null,
                    'defaults' => null,
                    'requirements' => null,
                    'options' => null,
                    'status' => null,
                    'schemes' => null,
                    'cacheHeaders' => null,
                    'normalizationContext' => [
                        'groups' => ['foo', 'bar'],
                        'enabled' => false,
                    ],
                    'denormalizationContext' => null,
                    'collectDenormalizationErrors' => null,
                    'hydraContext' => null,
                    'openapiContext' => null,
                    'openapi' => null,
                    'validationContext' => null,
                    'filters' => null,
                    'mercure' => null,
                    'messenger' => null,
                    'order' => null,
                    'paginationViaCursor' => null,
                    'exceptionToStatus' => null,
                    'extraProperties' => null,
                    'operations' => [
                        [
                            'name' => null,
                            'class' => GetCollection::class,
                            'uriTemplate' => '/users/{author}/programs{._format}',
                            'shortName' => null,
                            'description' => 'User programs',
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
                            'types' => ['someirischema'],
                            'formats' => null,
                            'uriVariables' => ['author' => 'author'],
                            'inputFormats' => null,
                            'outputFormats' => null,
                            'defaults' => null,
                            'requirements' => null,
                            'options' => null,
                            'status' => null,
                            'schemes' => null,
                            'cacheHeaders' => null,
                            'normalizationContext' => [
                                'groups' => ['foo', 'bar'],
                                'enabled' => false,
                            ],
                            'denormalizationContext' => null,
                            'collectDenormalizationErrors' => null,
                            'hydraContext' => null,
                            'openapiContext' => null,
                            'openapi' => null,
                            'validationContext' => null,
                            'filters' => null,
                            'mercure' => null,
                            'messenger' => null,
                            'order' => null,
                            'paginationViaCursor' => null,
                            'exceptionToStatus' => null,
                            'extraProperties' => null,
                            'read' => null,
                            'deserialize' => null,
                            'validate' => null,
                            'write' => null,
                            'serialize' => null,
                            'queryParameterValidate' => null,
                            'priority' => null,
                            'processor' => null,
                            'provider' => null,
                            'itemUriTemplate' => null,
                            'stateOptions' => null,
                            'links' => null,
                        ],
                        [
                            'name' => null,
                            'class' => Get::class,
                            'uriTemplate' => '/users/{userId}/programs/{id}{._format}',
                            'shortName' => null,
                            'description' => 'User programs',
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
                            'types' => ['anotheririschema'],
                            'formats' => null,
                            'uriVariables' => [
                                'userId' => ['from_class' => User::class, 'from_property' => 'author'],
                                'id' => 'id',
                            ],
                            'inputFormats' => null,
                            'outputFormats' => null,
                            'defaults' => null,
                            'requirements' => null,
                            'options' => null,
                            'status' => null,
                            'schemes' => null,
                            'cacheHeaders' => null,
                            'normalizationContext' => [
                                'groups' => ['foo', 'bar'],
                                'enabled' => false,
                            ],
                            'denormalizationContext' => null,
                            'collectDenormalizationErrors' => null,
                            'hydraContext' => null,
                            'openapiContext' => null,
                            'openapi' => null,
                            'validationContext' => null,
                            'filters' => null,
                            'mercure' => null,
                            'messenger' => null,
                            'order' => null,
                            'paginationViaCursor' => null,
                            'exceptionToStatus' => null,
                            'extraProperties' => [
                                'foo' => 'bar',
                                'boolean' => true,
                            ],
                            'read' => null,
                            'deserialize' => null,
                            'validate' => null,
                            'write' => null,
                            'serialize' => null,
                            'queryParameterValidate' => null,
                            'priority' => null,
                            'processor' => null,
                            'provider' => null,
                            'stateOptions' => null,
                            'links' => null,
                        ],
                    ],
                    'graphQlOperations' => null,
                    'processor' => null,
                    'provider' => null,
                    'read' => null,
                    'write' => null,
                    'stateOptions' => null,
                    'links' => null,
                ],
            ],
            SingleFileConfigDummy::class => [
                [
                    'uriTemplate' => null,
                    'shortName' => 'single_file_config',
                    'description' => 'File configured resource',
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
                    'filters' => null,
                    'mercure' => null,
                    'messenger' => null,
                    'order' => null,
                    'paginationViaCursor' => null,
                    'exceptionToStatus' => null,
                    'extraProperties' => null,
                    'operations' => null,
                    'graphQlOperations' => null,
                    'processor' => null,
                    'provider' => null,
                    'read' => null,
                    'write' => null,
                    'stateOptions' => null,
                    'links' => null,
                ],
            ],
        ], $extractor->getResources());
    }

    public function testOpenApiParameters(): void
    {
        $extractor = new YamlResourceExtractor([__DIR__.'/yaml/openapi.yaml']);
        $resources = $extractor->getResources();

        $this->assertArrayHasKey(Program::class, $resources);
        $this->assertArrayHasKey('openapi', $resources[Program::class][0]);

        $this->assertIsObject($resources[Program::class][0]['operations'][0]['openapi']);

        $operation = $resources[Program::class][0]['operations'][0]['openapi'];
        $this->assertIsArray($operation->getParameters());

        $this->assertEquals('author', $operation->getParameters()[0]->getName());
        $this->assertEquals('path', $operation->getParameters()[0]->getIn());
        $this->assertEquals('john-doe', $operation->getParameters()[0]->getExample());
    }

    public function testInputAndOutputAreBooleans(): void
    {
        $extractor = new YamlResourceExtractor([__DIR__.'/yaml/input-and-output-are-booleans.yaml']);
        $resources = $extractor->getResources();

        $this->assertArrayHasKey(Program::class, $resources);
        $this->assertArrayHasKey(0, $resources[Program::class]);
        $this->assertArrayHasKey('operations', $resources[Program::class][0]);
        $this->assertArrayHasKey('0', $resources[Program::class][0]['operations']);

        $this->assertArrayHasKey('input', $resources[Program::class][0]['operations'][0]);
        $this->assertFalse($resources[Program::class][0]['operations'][0]['input']);

        $this->assertArrayHasKey('output', $resources[Program::class][0]['operations'][0]);
        $this->assertFalse($resources[Program::class][0]['operations'][0]['output']);
    }

    public function testInputAndOutputAreStrings(): void
    {
        $extractor = new YamlResourceExtractor([__DIR__.'/yaml/input-and-output-are-strings.yaml']);
        $resources = $extractor->getResources();

        $this->assertArrayHasKey(Program::class, $resources);
        $this->assertArrayHasKey(0, $resources[Program::class]);
        $this->assertArrayHasKey('operations', $resources[Program::class][0]);
        $this->assertArrayHasKey('0', $resources[Program::class][0]['operations']);

        $this->assertArrayHasKey('input', $resources[Program::class][0]['operations'][0]);
        $this->assertSame(Program::class.'Input', $resources[Program::class][0]['operations'][0]['input']);

        $this->assertArrayHasKey('output', $resources[Program::class][0]['operations'][0]);
        $this->assertSame(Program::class.'Output', $resources[Program::class][0]['operations'][0]['output']);
    }

    /**
     * @dataProvider getInvalidPaths
     */
    public function testInvalidYaml(string $path, string $error): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($error);

        (new YamlResourceExtractor([$path]))->getResources();
    }

    public static function getInvalidPaths(): array
    {
        return [
            [__DIR__.'/yaml/invalid/invalid_resources.yaml', '"resources" setting is expected to be null or an array, string given in "'.__DIR__.'/yaml/invalid/invalid_resources.yaml".'],
        ];
    }
}
