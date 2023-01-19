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

// Must be declared first!
if (!interface_exists(ApiPlatform\Core\Api\FilterInterface::class)) {
    class_alias(ApiPlatform\Api\FilterInterface::class, ApiPlatform\Core\Api\FilterInterface::class);
}
if (!interface_exists(ApiPlatform\Core\Api\ResourceClassResolverInterface::class)) {
    class_alias(ApiPlatform\Api\ResourceClassResolverInterface::class, ApiPlatform\Core\Api\ResourceClassResolverInterface::class);
}

$deprecatedInterfaces = include 'deprecated_interfaces.php';
foreach ($deprecatedInterfaces as $oldInterfaceName => $interfaceName) {
    // Do not replace existing interface
    if (interface_exists($oldInterfaceName)) {
        continue;
    }

    if (!interface_exists($interfaceName)) {
        dump("interface $interfaceName does not exist, replacement $oldInterfaceName neither");
        continue;
    }

    class_alias($interfaceName, $oldInterfaceName);
}

$deprecatedClassesWithAliases = [
    ApiPlatform\Core\Api\Entrypoint::class => ApiPlatform\Api\Entrypoint::class,
    ApiPlatform\Core\Api\FormatMatcher::class => ApiPlatform\Api\FormatMatcher::class,
    ApiPlatform\Core\Api\ResourceClassResolver::class => ApiPlatform\Api\ResourceClassResolver::class,

    ApiPlatform\Core\Metadata\Property\PropertyNameCollection::class => ApiPlatform\Metadata\Property\PropertyNameCollection::class,
    ApiPlatform\Core\Metadata\Property\Factory\CachedPropertyNameCollectionFactory::class => ApiPlatform\Metadata\Property\Factory\CachedPropertyNameCollectionFactory::class,
    ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory::class => ApiPlatform\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory::class,

    ApiPlatform\Core\Metadata\Resource\ResourceNameCollection::class => ApiPlatform\Metadata\Resource\ResourceNameCollection::class,

    // Test cases
    ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase::class => ApiPlatform\Symfony\Bundle\Test\ApiTestCase::class,
    ApiPlatform\Core\Test\DoctrineMongoDbOdmFilterTestCase::class => ApiPlatform\Test\DoctrineMongoDbOdmFilterTestCase::class,
    ApiPlatform\Core\Test\DoctrineMongoDbOdmTestCase::class => ApiPlatform\Test\DoctrineMongoDbOdmTestCase::class,
    ApiPlatform\Core\Test\DoctrineOrmFilterTestCase::class => ApiPlatform\Test\DoctrineOrmFilterTestCase::class,

    // Exceptions
    ApiPlatform\Core\Exception\DeserializationException::class => ApiPlatform\Exception\DeserializationException::class,
    ApiPlatform\Core\Exception\FilterValidationException::class => ApiPlatform\Exception\FilterValidationException::class,
    ApiPlatform\Core\Exception\InvalidArgumentException::class => ApiPlatform\Exception\InvalidArgumentException::class,
    ApiPlatform\Core\Exception\InvalidIdentifierException::class => ApiPlatform\Exception\InvalidIdentifierException::class,
    ApiPlatform\Core\Exception\InvalidResourceException::class => ApiPlatform\Exception\InvalidResourceException::class,
    ApiPlatform\Core\Exception\InvalidValueException::class => ApiPlatform\Exception\InvalidValueException::class,
    ApiPlatform\Core\Exception\PropertyNotFoundException::class => ApiPlatform\Exception\PropertyNotFoundException::class,
    ApiPlatform\Core\Exception\RuntimeException::class => ApiPlatform\Exception\RuntimeException::class,
    ApiPlatform\Core\Exception\ResourceClassNotFoundException::class => ApiPlatform\Exception\ResourceClassNotFoundException::class,
    ApiPlatform\Core\Exception\ResourceClassNotSupportedException::class => ApiPlatform\Exception\ResourceClassNotSupportedException::class,
    ApiPlatform\Core\Exception\ItemNotFoundException::class => ApiPlatform\Exception\ItemNotFoundException::class,

    // Action
    ApiPlatform\Core\Action\EntrypointAction::class => ApiPlatform\Action\EntrypointAction::class,
    ApiPlatform\Core\Action\ExceptionAction::class => ApiPlatform\Action\ExceptionAction::class,
    ApiPlatform\Core\Action\NotFoundAction::class => ApiPlatform\Action\NotFoundAction::class,
    ApiPlatform\Core\Action\PlaceholderAction::class => ApiPlatform\Action\PlaceholderAction::class,

    // Bridge\Doctrine\EventListener
    ApiPlatform\Core\Bridge\Doctrine\EventListener\PurgeHttpCacheListener::class => ApiPlatform\Doctrine\EventListener\PurgeHttpCacheListener::class,
    ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener::class => ApiPlatform\Doctrine\EventListener\WriteListener::class,

    // Bridge\Elasticsearch
    ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException::class => ApiPlatform\Elasticsearch\Exception\IndexNotFoundException::class,
    ApiPlatform\Core\Bridge\Elasticsearch\Exception\NonUniqueIdentifierException::class => ApiPlatform\Elasticsearch\Exception\NonUniqueIdentifierException::class,

    ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\AttributeDocumentMetadataFactory::class => ApiPlatform\Elasticsearch\Metadata\Document\Factory\AttributeDocumentMetadataFactory::class,
    ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\CachedDocumentMetadataFactory::class => ApiPlatform\Elasticsearch\Metadata\Document\Factory\CachedDocumentMetadataFactory::class,
    ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\CatDocumentMetadataFactory::class => ApiPlatform\Elasticsearch\Metadata\Document\Factory\CatDocumentMetadataFactory::class,
    ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\ConfiguredDocumentMetadataFactory::class => ApiPlatform\Elasticsearch\Metadata\Document\Factory\ConfiguredDocumentMetadataFactory::class,
    ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata::class => ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata::class,

    ApiPlatform\Core\Bridge\Elasticsearch\Serializer\NameConverter\InnerFieldsNameConverter::class => ApiPlatform\Elasticsearch\Serializer\NameConverter\InnerFieldsNameConverter::class,
    ApiPlatform\Core\Bridge\Elasticsearch\Util\FieldDatatypeTrait::class => ApiPlatform\Elasticsearch\Util\FieldDatatypeTrait::class,

    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Paginator::class => ApiPlatform\Elasticsearch\Paginator::class,

    // Bridge\RamseyUuid
    ApiPlatform\Core\Bridge\RamseyUuid\Serializer\UuidDenormalizer::class => ApiPlatform\RamseyUuid\Serializer\UuidDenormalizer::class,

    // Bridge\Symfony\Bundle
    ApiPlatform\Core\Bridge\Symfony\Bundle\ArgumentResolver\PayloadArgumentResolver::class => ApiPlatform\Symfony\Bundle\ArgumentResolver\PayloadArgumentResolver::class,

    ApiPlatform\Core\Bridge\Symfony\Bundle\CacheWarmer\CachePoolClearerCacheWarmer::class => ApiPlatform\Symfony\Bundle\CacheWarmer\CachePoolClearerCacheWarmer::class,

    ApiPlatform\Core\Bridge\Symfony\Bundle\Command\GraphQlExportCommand::class => ApiPlatform\Symfony\Bundle\Command\GraphQlExportCommand::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\Command\OpenApiCommand::class => ApiPlatform\Symfony\Bundle\Command\OpenApiCommand::class,

    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\AnnotationFilterPass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\AnnotationFilterPass::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\AuthenticatorManagerPass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\AuthenticatorManagerPass::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DataProviderPass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\DataProviderPass::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DeprecateMercurePublisherPass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\DeprecateMercurePublisherPass::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\ElasticsearchClientPass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\ElasticsearchClientPass::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\FilterPass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\FilterPass::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\GraphQlMutationResolverPass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlMutationResolverPass::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\GraphQlQueryResolverPass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlQueryResolverPass::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\GraphQlTypePass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlTypePass::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\MetadataAwareNameConverterPass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\MetadataAwareNameConverterPass::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\TestClientPass::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\TestClientPass::class,

    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\ApiPlatformExtension::class => ApiPlatform\Symfony\Bundle\DependencyInjection\ApiPlatformExtension::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Configuration::class => ApiPlatform\Symfony\Bundle\DependencyInjection\Configuration::class,

    ApiPlatform\Core\Bridge\Symfony\Bundle\EventListener\SwaggerUiListener::class => ApiPlatform\Symfony\Bundle\EventListener\SwaggerUiListener::class,

    ApiPlatform\Core\Bridge\Symfony\Bundle\SwaggerUi\SwaggerUiAction::class => ApiPlatform\Symfony\Bundle\SwaggerUi\SwaggerUiAction::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\SwaggerUi\SwaggerUiContext::class => ApiPlatform\Symfony\Bundle\SwaggerUi\SwaggerUiContext::class,

    ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestAssertionsTrait::class => ApiPlatform\Symfony\Bundle\Test\ApiTestAssertionsTrait::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client::class => ApiPlatform\Symfony\Bundle\Test\Client::class,
    ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Response::class => ApiPlatform\Symfony\Bundle\Test\Response::class,

    ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle::class => ApiPlatform\Symfony\Bundle\ApiPlatformBundle::class,

    // Bridge\Symfony\Messenger
    ApiPlatform\Core\Bridge\Symfony\Messenger\ContextStamp::class => ApiPlatform\Symfony\Messenger\ContextStamp::class,
    ApiPlatform\Core\Bridge\Symfony\Messenger\DispatchTrait::class => ApiPlatform\Symfony\Messenger\DispatchTrait::class,
    ApiPlatform\Core\Bridge\Symfony\Messenger\RemoveStamp::class => ApiPlatform\Symfony\Messenger\RemoveStamp::class,

    // Bridge\Symfony\PropertyInfo\Metadata\Property => Metadata\Property
    ApiPlatform\Core\Bridge\Symfony\PropertyInfo\Metadata\Property\PropertyInfoPropertyNameCollectionFactory::class => ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyNameCollectionFactory::class,

    // Bridge\Symfony\Routing
    ApiPlatform\Core\Bridge\Symfony\Routing\ApiLoader::class => ApiPlatform\Symfony\Routing\ApiLoader::class,
    ApiPlatform\Core\Bridge\Symfony\Routing\Router::class => ApiPlatform\Symfony\Routing\Router::class,

    // Bridge\Symfony\Validator
    ApiPlatform\Core\Bridge\Symfony\Validator\EventListener\ValidationExceptionListener::class => ApiPlatform\Symfony\Validator\EventListener\ValidationExceptionListener::class,
    ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException::class => ApiPlatform\Symfony\Validator\Exception\ValidationException::class,
    ApiPlatform\Core\Bridge\Symfony\Validator\Validator::class => ApiPlatform\Symfony\Validator\Validator::class,

    // Cache
    ApiPlatform\Core\Cache\CachedTrait::class => ApiPlatform\Util\CachedTrait::class,

    // DataProvider => State/Pagination
    ApiPlatform\Core\DataProvider\ArrayPaginator::class => ApiPlatform\State\Pagination\ArrayPaginator::class,
    ApiPlatform\Core\DataProvider\PaginationOptions::class => ApiPlatform\State\Pagination\PaginationOptions::class,
    ApiPlatform\Core\DataProvider\TraversablePaginator::class => ApiPlatform\State\Pagination\TraversablePaginator::class,

    // Documentation
    ApiPlatform\Core\Documentation\Action\DocumentationAction::class => ApiPlatform\Documentation\Action\DocumentationAction::class,
    ApiPlatform\Core\Documentation\Documentation::class => ApiPlatform\Documentation\Documentation::class,

    // EventListener => Symfony/EventListener
    ApiPlatform\Core\EventListener\AddFormatListener::class => ApiPlatform\Symfony\EventListener\AddFormatListener::class,
    ApiPlatform\Core\EventListener\DeserializeListener::class => ApiPlatform\Symfony\EventListener\DeserializeListener::class,
    ApiPlatform\Core\EventListener\EventPriorities::class => ApiPlatform\Symfony\EventListener\EventPriorities::class,
    ApiPlatform\Core\EventListener\ExceptionListener::class => ApiPlatform\Symfony\EventListener\ExceptionListener::class,
    ApiPlatform\Core\EventListener\QueryParameterValidateListener::class => ApiPlatform\Symfony\EventListener\QueryParameterValidateListener::class,
    ApiPlatform\Core\EventListener\RespondListener::class => ApiPlatform\Symfony\EventListener\RespondListener::class,
    ApiPlatform\Core\EventListener\SerializeListener::class => ApiPlatform\Symfony\EventListener\SerializeListener::class,

    // Hal
    ApiPlatform\Core\Hal\Serializer\CollectionNormalizer::class => ApiPlatform\Hal\Serializer\CollectionNormalizer::class,
    ApiPlatform\Core\Hal\Serializer\EntrypointNormalizer::class => ApiPlatform\Hal\Serializer\EntrypointNormalizer::class,
    ApiPlatform\Core\Hal\Serializer\ItemNormalizer::class => ApiPlatform\Hal\Serializer\ItemNormalizer::class,
    ApiPlatform\Core\Hal\Serializer\ObjectNormalizer::class => ApiPlatform\Hal\Serializer\ObjectNormalizer::class,

    // HttpCache
    ApiPlatform\Core\HttpCache\EventListener\AddHeadersListener::class => ApiPlatform\HttpCache\EventListener\AddHeadersListener::class,
    ApiPlatform\Core\HttpCache\EventListener\AddTagsListener::class => ApiPlatform\HttpCache\EventListener\AddTagsListener::class,
    ApiPlatform\Core\HttpCache\VarnishPurger::class => ApiPlatform\HttpCache\VarnishPurger::class,
    ApiPlatform\Core\HttpCache\VarnishXKeyPurger::class => ApiPlatform\HttpCache\VarnishXKeyPurger::class,

    // Hydra
    ApiPlatform\Core\Hydra\EventListener\AddLinkHeaderListener::class => ApiPlatform\Hydra\EventListener\AddLinkHeaderListener::class,
    ApiPlatform\Core\Hydra\JsonSchema\SchemaFactory::class => ApiPlatform\Hydra\JsonSchema\SchemaFactory::class,
    ApiPlatform\Core\Hydra\Serializer\CollectionFiltersNormalizer::class => ApiPlatform\Hydra\Serializer\CollectionFiltersNormalizer::class,
    ApiPlatform\Core\Hydra\Serializer\CollectionNormalizer::class => ApiPlatform\Hydra\Serializer\CollectionNormalizer::class,
    ApiPlatform\Core\Hydra\Serializer\ConstraintViolationListNormalizer::class => ApiPlatform\Hydra\Serializer\ConstraintViolationListNormalizer::class,
    ApiPlatform\Core\Hydra\Serializer\DocumentationNormalizer::class => ApiPlatform\Hydra\Serializer\DocumentationNormalizer::class,
    ApiPlatform\Core\Hydra\Serializer\EntrypointNormalizer::class => ApiPlatform\Hydra\Serializer\EntrypointNormalizer::class,
    ApiPlatform\Core\Hydra\Serializer\ErrorNormalizer::class => ApiPlatform\Hydra\Serializer\ErrorNormalizer::class,
    ApiPlatform\Core\Hydra\Serializer\PartialCollectionViewNormalizer::class => ApiPlatform\Hydra\Serializer\PartialCollectionViewNormalizer::class,

    // JsonApi/EventListener => Symfony/EventListener
    ApiPlatform\Core\JsonApi\EventListener\TransformFieldsetsParametersListener::class => ApiPlatform\Symfony\EventListener\JsonApi\TransformFieldsetsParametersListener::class,
    ApiPlatform\Core\JsonApi\EventListener\TransformFilteringParametersListener::class => ApiPlatform\Symfony\EventListener\JsonApi\TransformFilteringParametersListener::class,
    ApiPlatform\Core\JsonApi\EventListener\TransformPaginationParametersListener::class => ApiPlatform\Symfony\EventListener\JsonApi\TransformPaginationParametersListener::class,
    ApiPlatform\Core\JsonApi\EventListener\TransformSortingParametersListener::class => ApiPlatform\Symfony\EventListener\JsonApi\TransformSortingParametersListener::class,

    // JsonApi/Serializer
    ApiPlatform\Core\JsonApi\Serializer\CollectionNormalizer::class => ApiPlatform\JsonApi\Serializer\CollectionNormalizer::class,
    ApiPlatform\Core\JsonApi\Serializer\ConstraintViolationListNormalizer::class => ApiPlatform\JsonApi\Serializer\ConstraintViolationListNormalizer::class,
    ApiPlatform\Core\JsonApi\Serializer\EntrypointNormalizer::class => ApiPlatform\JsonApi\Serializer\EntrypointNormalizer::class,
    ApiPlatform\Core\JsonApi\Serializer\ErrorNormalizer::class => ApiPlatform\JsonApi\Serializer\ErrorNormalizer::class,
    ApiPlatform\Core\JsonApi\Serializer\ItemNormalizer::class => ApiPlatform\JsonApi\Serializer\ItemNormalizer::class,
    ApiPlatform\Core\JsonApi\Serializer\ObjectNormalizer::class => ApiPlatform\JsonApi\Serializer\ObjectNormalizer::class,
    ApiPlatform\Core\JsonApi\Serializer\ReservedAttributeNameConverter::class => ApiPlatform\JsonApi\Serializer\ReservedAttributeNameConverter::class,

    // JsonLd
    ApiPlatform\Core\JsonLd\Action\ContextAction::class => ApiPlatform\JsonLd\Action\ContextAction::class,
    ApiPlatform\Core\JsonLd\Serializer\ItemNormalizer::class => ApiPlatform\JsonLd\Serializer\ItemNormalizer::class,
    ApiPlatform\Core\JsonLd\Serializer\JsonLdContextTrait::class => ApiPlatform\JsonLd\Serializer\JsonLdContextTrait::class,
    ApiPlatform\Core\JsonLd\Serializer\ObjectNormalizer::class => ApiPlatform\JsonLd\Serializer\ObjectNormalizer::class,
    ApiPlatform\Core\JsonLd\ContextBuilder::class => ApiPlatform\JsonLd\ContextBuilder::class,

    // JsonSchema
    ApiPlatform\Core\JsonSchema\Command\JsonSchemaGenerateCommand::class => ApiPlatform\JsonSchema\Command\JsonSchemaGenerateCommand::class,
    ApiPlatform\Core\JsonSchema\Schema::class => ApiPlatform\JsonSchema\Schema::class,
    ApiPlatform\Core\JsonSchema\TypeFactory::class => ApiPlatform\JsonSchema\TypeFactory::class,

    // Mercure/EventListener => Symfony/EventListener
    ApiPlatform\Core\Mercure\EventListener\AddLinkHeaderListener::class => ApiPlatform\Symfony\EventListener\AddLinkHeaderListener::class,

    // OpenApi
    ApiPlatform\Core\OpenApi\Model\Components::class => ApiPlatform\OpenApi\Model\Components::class,
    ApiPlatform\Core\OpenApi\Model\Contact::class => ApiPlatform\OpenApi\Model\Contact::class,
    ApiPlatform\Core\OpenApi\Model\Encoding::class => ApiPlatform\OpenApi\Model\Encoding::class,
    ApiPlatform\Core\OpenApi\Model\ExtensionTrait::class => ApiPlatform\OpenApi\Model\ExtensionTrait::class,
    ApiPlatform\Core\OpenApi\Model\ExternalDocumentation::class => ApiPlatform\OpenApi\Model\ExternalDocumentation::class,
    ApiPlatform\Core\OpenApi\Model\Info::class => ApiPlatform\OpenApi\Model\Info::class,
    ApiPlatform\Core\OpenApi\Model\License::class => ApiPlatform\OpenApi\Model\License::class,
    ApiPlatform\Core\OpenApi\Model\Link::class => ApiPlatform\OpenApi\Model\Link::class,
    ApiPlatform\Core\OpenApi\Model\MediaType::class => ApiPlatform\OpenApi\Model\MediaType::class,
    ApiPlatform\Core\OpenApi\Model\OAuthFlow::class => ApiPlatform\OpenApi\Model\OAuthFlow::class,
    ApiPlatform\Core\OpenApi\Model\OAuthFlows::class => ApiPlatform\OpenApi\Model\OAuthFlows::class,
    ApiPlatform\Core\OpenApi\Model\Parameter::class => ApiPlatform\OpenApi\Model\Parameter::class,
    ApiPlatform\Core\OpenApi\Model\Paths::class => ApiPlatform\OpenApi\Model\Paths::class,
    ApiPlatform\Core\OpenApi\Model\PathItem::class => ApiPlatform\OpenApi\Model\PathItem::class,
    ApiPlatform\Core\OpenApi\Model\Operation::class => ApiPlatform\OpenApi\Model\Operation::class,
    ApiPlatform\Core\OpenApi\Model\RequestBody::class => ApiPlatform\OpenApi\Model\RequestBody::class,
    ApiPlatform\Core\OpenApi\Model\Response::class => ApiPlatform\OpenApi\Model\Response::class,
    ApiPlatform\Core\OpenApi\Model\Schema::class => ApiPlatform\OpenApi\Model\Schema::class,
    ApiPlatform\Core\OpenApi\Model\SecurityScheme::class => ApiPlatform\OpenApi\Model\SecurityScheme::class,
    ApiPlatform\Core\OpenApi\Model\Server::class => ApiPlatform\OpenApi\Model\Server::class,
    ApiPlatform\Core\OpenApi\Serializer\OpenApiNormalizer::class => ApiPlatform\OpenApi\Serializer\OpenApiNormalizer::class,
    ApiPlatform\Core\OpenApi\OpenApi::class => ApiPlatform\OpenApi\OpenApi::class,
    ApiPlatform\Core\OpenApi\Options::class => ApiPlatform\OpenApi\Options::class,

    // PathResolver
    ApiPlatform\Core\PathResolver\CustomOperationPathResolver::class => ApiPlatform\PathResolver\CustomOperationPathResolver::class,
    ApiPlatform\Core\PathResolver\OperationPathResolver::class => ApiPlatform\PathResolver\OperationPathResolver::class,

    // Problem
    ApiPlatform\Core\Problem\Serializer\ConstraintViolationListNormalizer::class => ApiPlatform\Problem\Serializer\ConstraintViolationListNormalizer::class,
    ApiPlatform\Core\Problem\Serializer\ErrorNormalizer::class => ApiPlatform\Problem\Serializer\ErrorNormalizer::class,
    ApiPlatform\Core\Problem\Serializer\ErrorNormalizerTrait::class => ApiPlatform\Problem\Serializer\ErrorNormalizerTrait::class,

    // Security => Symfony/Security
    ApiPlatform\Core\Security\Core\Authorization\ExpressionLanguageProvider::class => ApiPlatform\Symfony\Security\Core\Authorization\ExpressionLanguageProvider::class,
    ApiPlatform\Core\Security\ExpressionLanguage::class => ApiPlatform\Symfony\Security\ExpressionLanguage::class,
    ApiPlatform\Core\Security\ResourceAccessChecker::class => ApiPlatform\Symfony\Security\ResourceAccessChecker::class,

    // Security/EventListener => Symfony/EventListener
    ApiPlatform\Core\Security\EventListener\DenyAccessListener::class => ApiPlatform\Symfony\EventListener\DenyAccessListener::class,

    // Serializer
    ApiPlatform\Core\Serializer\Filter\GroupFilter::class => ApiPlatform\Serializer\Filter\GroupFilter::class,
    ApiPlatform\Core\Serializer\Filter\PropertyFilter::class => ApiPlatform\Serializer\Filter\PropertyFilter::class,
    ApiPlatform\Core\Serializer\Mapping\Factory\ClassMetadataFactory::class => ApiPlatform\Serializer\Mapping\Factory\ClassMetadataFactory::class,
    ApiPlatform\Core\Serializer\AbstractCollectionNormalizer::class => ApiPlatform\Serializer\AbstractCollectionNormalizer::class,
    ApiPlatform\Core\Serializer\AbstractConstraintViolationListNormalizer::class => ApiPlatform\Serializer\AbstractConstraintViolationListNormalizer::class,
    ApiPlatform\Core\Serializer\AbstractItemNormalizer::class => ApiPlatform\Serializer\AbstractItemNormalizer::class,
    ApiPlatform\Core\Serializer\CacheKeyTrait::class => ApiPlatform\Serializer\CacheKeyTrait::class,
    ApiPlatform\Core\Serializer\ContextTrait::class => ApiPlatform\Serializer\ContextTrait::class,
    ApiPlatform\Core\Serializer\InputOutputMetadataTrait::class => ApiPlatform\Serializer\InputOutputMetadataTrait::class,
    ApiPlatform\Core\Serializer\ItemNormalizer::class => ApiPlatform\Serializer\ItemNormalizer::class,
    ApiPlatform\Core\Serializer\JsonEncoder::class => ApiPlatform\Serializer\JsonEncoder::class,
    ApiPlatform\Core\Serializer\ResourceList::class => ApiPlatform\Serializer\ResourceList::class,
    ApiPlatform\Core\Serializer\SerializerContextBuilder::class => ApiPlatform\Serializer\SerializerContextBuilder::class,
    ApiPlatform\Core\Serializer\SerializerFilterContextBuilder::class => ApiPlatform\Serializer\SerializerFilterContextBuilder::class,

    // Swagger/Serializer => OpenApi/Serializer
    ApiPlatform\Core\Swagger\Serializer\ApiGatewayNormalizer::class => ApiPlatform\OpenApi\Serializer\ApiGatewayNormalizer::class,

    // Test
    ApiPlatform\Core\Test\DoctrineMongoDbOdmSetup::class => ApiPlatform\Test\DoctrineMongoDbOdmSetup::class,

    // Util
    ApiPlatform\Core\Util\AnnotationFilterExtractorTrait::class => ApiPlatform\Util\AnnotationFilterExtractorTrait::class,
    ApiPlatform\Core\Util\ArrayTrait::class => ApiPlatform\Util\ArrayTrait::class,
    ApiPlatform\Core\Util\AttributesExtractor::class => ApiPlatform\Util\AttributesExtractor::class,
    ApiPlatform\Core\Util\ClassInfoTrait::class => ApiPlatform\Util\ClassInfoTrait::class,
    ApiPlatform\Core\Util\ClientTrait::class => ApiPlatform\Util\ClientTrait::class,
    ApiPlatform\Core\Util\CloneTrait::class => ApiPlatform\Util\CloneTrait::class,
    ApiPlatform\Core\Util\CorsTrait::class => ApiPlatform\Util\CorsTrait::class,
    ApiPlatform\Core\Util\ErrorFormatGuesser::class => ApiPlatform\Util\ErrorFormatGuesser::class,
    ApiPlatform\Core\Util\Inflector::class => ApiPlatform\Util\Inflector::class,
    ApiPlatform\Core\Util\IriHelper::class => ApiPlatform\Util\IriHelper::class,
    ApiPlatform\Core\Util\Reflection::class => ApiPlatform\Util\Reflection::class,
    ApiPlatform\Core\Util\ReflectionClassRecursiveIterator::class => ApiPlatform\Util\ReflectionClassRecursiveIterator::class,
    ApiPlatform\Core\Util\RequestAttributesExtractor::class => ApiPlatform\Util\RequestAttributesExtractor::class,
    ApiPlatform\Core\Util\RequestParser::class => ApiPlatform\Util\RequestParser::class,
    ApiPlatform\Core\Util\ResourceClassInfoTrait::class => ApiPlatform\Util\ResourceClassInfoTrait::class,
    ApiPlatform\Core\Util\ResponseTrait::class => ApiPlatform\Util\ResponseTrait::class,
    ApiPlatform\Core\Util\SortTrait::class => ApiPlatform\Util\SortTrait::class,

    // Validator
    ApiPlatform\Core\Validator\EventListener\ValidateListener::class => ApiPlatform\Symfony\EventListener\ValidateListener::class,
    ApiPlatform\Core\Validator\Exception\ValidationException::class => ApiPlatform\Validator\Exception\ValidationException::class,

    ApiPlatform\Core\Filter\QueryParameterValidator::class => ApiPlatform\Api\QueryParameterValidator\QueryParameterValidator::class,
    ApiPlatform\Core\Filter\Validator\ArrayItems::class => ApiPlatform\Api\QueryParameterValidator\Validator\ArrayItems::class,
    ApiPlatform\Core\Filter\Validator\Bounds::class => ApiPlatform\Api\QueryParameterValidator\Validator\Bounds::class,
    ApiPlatform\Core\Filter\Validator\Enum::class => ApiPlatform\Api\QueryParameterValidator\Validator\Enum::class,
    ApiPlatform\Core\Filter\Validator\Length::class => ApiPlatform\Api\QueryParameterValidator\Validator\Length::class,
    ApiPlatform\Core\Filter\Validator\MultipleOf::class => ApiPlatform\Api\QueryParameterValidator\Validator\MultipleOf::class,
    ApiPlatform\Core\Filter\Validator\Pattern::class => ApiPlatform\Api\QueryParameterValidator\Validator\Pattern::class,
    ApiPlatform\Core\Filter\Validator\Required::class => ApiPlatform\Api\QueryParameterValidator\Validator\Required::class,
    ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator::class => ApiPlatform\Operation\UnderscorePathSegmentNameGenerator::class,
    ApiPlatform\Core\Operation\DashPathSegmentNameGenerator::class => ApiPlatform\Operation\DashPathSegmentNameGenerator::class,

    // GraphQl
    ApiPlatform\Core\GraphQl\Executor::class => ApiPlatform\GraphQl\Executor::class,
    ApiPlatform\Core\GraphQl\Error\ErrorHandler::class => ApiPlatform\GraphQl\Error\ErrorHandler::class,
    ApiPlatform\Core\GraphQl\Resolver\Util\IdentifierTrait::class => ApiPlatform\GraphQl\Resolver\Util\IdentifierTrait::class,
    ApiPlatform\Core\GraphQl\Action\EntrypointAction::class => ApiPlatform\GraphQl\Action\EntrypointAction::class,
    ApiPlatform\Core\GraphQl\Action\GraphiQlAction::class => ApiPlatform\GraphQl\Action\GraphiQlAction::class,
    ApiPlatform\Core\GraphQl\Action\GraphQlPlaygroundAction::class => ApiPlatform\GraphQl\Action\GraphQlPlaygroundAction::class,
    ApiPlatform\Core\GraphQl\Type\TypeNotFoundException::class => ApiPlatform\GraphQl\Type\TypeNotFoundException::class,
    ApiPlatform\Core\GraphQl\Type\TypesFactory::class => ApiPlatform\GraphQl\Type\TypesFactory::class,
    ApiPlatform\Core\GraphQl\Type\Definition\UploadType::class => ApiPlatform\GraphQl\Type\Definition\UploadType::class,
    ApiPlatform\Core\GraphQl\Type\Definition\IterableType::class => ApiPlatform\GraphQl\Type\Definition\IterableType::class,
    ApiPlatform\Core\GraphQl\Type\TypesContainer::class => ApiPlatform\GraphQl\Type\TypesContainer::class,

    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\PropertyInfo\DoctrineExtractor::class => ApiPlatform\Doctrine\Odm\PropertyInfo\DoctrineExtractor::class,
];

// These classes are deprecated but we don't want aliases as the interfaces changed
$deprecatedClassesWithoutAliases = [
    ApiPlatform\Core\JsonSchema\SchemaFactory::class => ApiPlatform\JsonSchema\SchemaFactory::class,
    ApiPlatform\Core\Api\FilterLocatorTrait::class => ApiPlatform\Api\FilterLocatorTrait::class,

    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\AbstractFilter::class => ApiPlatform\Elasticsearch\Filter\AbstractFilter::class,
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\AbstractSearchFilter::class => ApiPlatform\Elasticsearch\Filter\AbstractSearchFilter::class,
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\MatchFilter::class => ApiPlatform\Elasticsearch\Filter\MatchFilter::class,
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\OrderFilter::class => ApiPlatform\Elasticsearch\Filter\OrderFilter::class,
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\TermFilter::class => ApiPlatform\Elasticsearch\Filter\TermFilter::class,

    ApiPlatform\Core\DataProvider\Pagination::class => ApiPlatform\State\Pagination\Pagination::class,

    ApiPlatform\Core\Metadata\Property\Factory\SerializerPropertyMetadataFactory::class => ApiPlatform\Metadata\Property\Factory\SerializerPropertyMetadataFactory::class,
    ApiPlatform\Core\Metadata\Property\Factory\CachedPropertyMetadataFactory::class => ApiPlatform\Metadata\Property\Factory\CachedPropertyMetadataFactory::class,
    ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyMetadataFactory::class => ApiPlatform\Metadata\Property\Factory\ExtractorPropertyMetadataFactory::class,
    ApiPlatform\Core\Metadata\Property\Factory\DefaultPropertyMetadataFactory::class => ApiPlatform\Metadata\Property\Factory\DefaultPropertyMetadataFactory::class,

    ApiPlatform\Core\Metadata\Extractor\XmlExtractor::class => ApiPlatform\Metadata\Extractor\XmlResourceExtractor::class,
    ApiPlatform\Core\Metadata\Extractor\YamlExtractor::class => ApiPlatform\Metadata\Extractor\YamlResourceExtractor::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Metadata\Property\DoctrineMongoDbOdmPropertyMetadataFactory::class => ApiPlatform\Doctrine\Odm\Metadata\Property\DoctrineMongoDbOdmPropertyMetadataFactory::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Metadata\Property\DoctrineOrmPropertyMetadataFactory::class => ApiPlatform\Doctrine\Orm\Metadata\Property\DoctrineOrmPropertyMetadataFactory::class,
    ApiPlatform\Core\Bridge\Symfony\PropertyInfo\Metadata\Property\PropertyInfoPropertyMetadataFactory::class => ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyMetadataFactory::class,
    ApiPlatform\Core\Metadata\Property\PropertyMetadata::class => ApiPlatform\Metadata\ApiProperty::class,

    ApiPlatform\Core\GraphQl\Resolver\Stage\ReadStage::class => ApiPlatform\GraphQl\Resolver\Stage\ReadStage::class,
    ApiPlatform\Core\GraphQl\Resolver\Stage\WriteStage::class => ApiPlatform\GraphQl\Resolver\Stage\WriteStage::class,
    ApiPlatform\Core\GraphQl\Type\TypeConverter::class => ApiPlatform\GraphQl\Type\TypeConverter::class,
    ApiPlatform\Core\GraphQl\Type\TypeBuilder::class => ApiPlatform\GraphQl\Type\TypeBuilder::class,
    ApiPlatform\Core\GraphQl\Type\FieldsBuilder::class => ApiPlatform\GraphQl\Type\FieldsBuilder::class,
    ApiPlatform\Core\GraphQl\Type\SchemaBuilder::class => ApiPlatform\GraphQl\Type\SchemaBuilder::class,
    ApiPlatform\Core\GraphQl\Serializer\SerializerContextBuilder::class => ApiPlatform\GraphQl\Serializer\SerializerContextBuilder::class,
    ApiPlatform\Core\GraphQl\Resolver\Factory\ItemResolverFactory::class => ApiPlatform\GraphQl\Resolver\Factory\ItemResolverFactory::class,
    ApiPlatform\Core\GraphQl\Resolver\Factory\CollectionResolverFactory::class => ApiPlatform\GraphQl\Resolver\Factory\CollectionResolverFactory::class,
    ApiPlatform\Core\GraphQl\Resolver\Factory\ItemMutationResolverFactory::class => ApiPlatform\GraphQl\Resolver\Factory\ItemMutationResolverFactory::class,
    ApiPlatform\Core\GraphQl\Resolver\Factory\ItemSubscriptionResolverFactory::class => ApiPlatform\GraphQl\Resolver\Factory\ItemSubscriptionResolverFactory::class,
    ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityStage::class => ApiPlatform\GraphQl\Resolver\Stage\SecurityStage::class,
    ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityPostDenormalizeStage::class => ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStage::class,
    ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStage::class => ApiPlatform\GraphQl\Resolver\Stage\SerializeStage::class,
    ApiPlatform\Core\GraphQl\Resolver\Stage\DeserializeStage::class => ApiPlatform\GraphQl\Resolver\Stage\DeserializeStage::class,
    ApiPlatform\Core\GraphQl\Resolver\Stage\ValidateStage::class => ApiPlatform\GraphQl\Resolver\Stage\ValidateStage::class,
    ApiPlatform\Core\GraphQl\Resolver\ResourceFieldResolver::class => ApiPlatform\GraphQl\Resolver\ResourceFieldResolver::class,
    ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer::class => ApiPlatform\GraphQl\Serializer\ItemNormalizer::class,
    ApiPlatform\Core\GraphQl\Serializer\ObjectNormalizer::class => ApiPlatform\GraphQl\Serializer\ObjectNormalizer::class,
    ApiPlatform\Core\GraphQl\Subscription\SubscriptionManager::class => ApiPlatform\GraphQl\Subscription\SubscriptionManager::class,
    ApiPlatform\Core\GraphQl\Serializer\Exception\ErrorNormalizer::class => ApiPlatform\GraphQl\Serializer\Exception\ErrorNormalizer::class,
    ApiPlatform\Core\GraphQl\Serializer\Exception\ValidationExceptionNormalizer::class => ApiPlatform\GraphQl\Serializer\Exception\ValidationExceptionNormalizer::class,
    ApiPlatform\Core\GraphQl\Serializer\Exception\HttpExceptionNormalizer::class => ApiPlatform\GraphQl\Serializer\Exception\HttpExceptionNormalizer::class,
    ApiPlatform\Core\GraphQl\Serializer\Exception\RuntimeExceptionNormalizer::class => ApiPlatform\GraphQl\Serializer\Exception\RuntimeExceptionNormalizer::class,
    ApiPlatform\Core\GraphQl\Subscription\SubscriptionIdentifierGenerator::class => ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGenerator::class,
    ApiPlatform\Core\GraphQl\Subscription\MercureSubscriptionIriGenerator::class => ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGenerator::class,

    ApiPlatform\Core\Bridge\Doctrine\EventListener\PublishMercureUpdatesListener::class => ApiPlatform\Doctrine\EventListener\PublishMercureUpdatesListener::class,

    ApiPlatform\Core\HttpCache\PurgerInterface::class => ApiPlatform\HttpCache\PurgerInterface::class,

    // Bridge\Doctrine
    ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait::class => ApiPlatform\Doctrine\Common\PropertyHelperTrait::class,

    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\BooleanFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\BooleanFilterTrait::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\DateFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\DateFilterTrait::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\ExistsFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\ExistsFilterTrait::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\NumericFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\NumericFilterTrait::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\OrderFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\OrderFilterTrait::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\RangeFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\RangeFilterTrait::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\SearchFilterTrait::class,

    // Bridge\Doctrine\MongoDbOdm
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\FilterExtension::class => ApiPlatform\Doctrine\Odm\Extension\FilterExtension::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\OrderExtension::class => ApiPlatform\Doctrine\Orm\Extension\OrderExtension::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\PaginationExtension::class => ApiPlatform\Doctrine\Orm\Extension\PaginationExtension::class,

    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\AbstractFilter::class => ApiPlatform\Doctrine\Odm\Filter\AbstractFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\BooleanFilter::class => ApiPlatform\Doctrine\Odm\Filter\BooleanFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\DateFilter::class => ApiPlatform\Doctrine\Odm\Filter\DateFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\ExistsFilter::class => ApiPlatform\Doctrine\Odm\Filter\ExistsFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\NumericFilter::class => ApiPlatform\Doctrine\Odm\Filter\NumericFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\OrderFilter::class => ApiPlatform\Doctrine\Odm\Filter\OrderFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\RangeFilter::class => ApiPlatform\Doctrine\Odm\Filter\RangeFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\SearchFilter::class => ApiPlatform\Doctrine\Odm\Filter\SearchFilter::class,

    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Paginator::class => ApiPlatform\Doctrine\Odm\Paginator::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\PropertyHelperTrait::class => ApiPlatform\Doctrine\Odm\PropertyHelperTrait::class,

    // Bridge\Doctrine\Orm
    ApiPlatform\Core\Bridge\Doctrine\Orm\AbstractPaginator::class => ApiPlatform\Doctrine\Orm\AbstractPaginator::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator::class => ApiPlatform\Doctrine\Orm\Paginator::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\PropertyHelperTrait::class => ApiPlatform\Doctrine\Orm\PropertyHelperTrait::class,

    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension::class => ApiPlatform\Doctrine\Orm\Extension\EagerLoadingExtension::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension::class => ApiPlatform\Doctrine\Orm\Extension\FilterEagerLoadingExtension::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterExtension::class => ApiPlatform\Doctrine\Orm\Extension\FilterExtension::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\OrderExtension::class => ApiPlatform\Doctrine\Orm\Extension\OrderExtension::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\PaginationExtension::class => ApiPlatform\Doctrine\Orm\Extension\PaginationExtension::class,

    ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter::class => ApiPlatform\Doctrine\Orm\Filter\AbstractFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter::class => ApiPlatform\Doctrine\Orm\Filter\BooleanFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter::class => ApiPlatform\Doctrine\Orm\Filter\DateFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter::class => ApiPlatform\Doctrine\Orm\Filter\ExistsFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter::class => ApiPlatform\Doctrine\Orm\Filter\NumericFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter::class => ApiPlatform\Doctrine\Orm\Filter\OrderFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter::class => ApiPlatform\Doctrine\Orm\Filter\RangeFilter::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter::class => ApiPlatform\Doctrine\Orm\Filter\SearchFilter::class,

    ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper::class => ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker::class => ApiPlatform\Doctrine\Orm\Util\QueryChecker::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator::class => ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator::class,

    // Bridge\Elasticsearch
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\AbstractFilterExtension::class => ApiPlatform\Elasticsearch\Extension\AbstractFilterExtension::class,
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\ConstantScoreFilterExtension::class => ApiPlatform\Elasticsearch\Extension\ConstantScoreFilterExtension::class,
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\SortExtension::class => ApiPlatform\Elasticsearch\Extension\SortExtension::class,
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\SortFilterExtension::class => ApiPlatform\Elasticsearch\Extension\SortFilterExtension::class,
];

spl_autoload_register(function ($className) use ($deprecatedInterfaces, $deprecatedClassesWithoutAliases, $deprecatedClassesWithAliases) {
    // We can not use class alias when working with doctrine annotations
    static $deprecatedAnnotations = [
        'ApiResource' => [ApiPlatform\Core\Annotation\ApiResource::class, ApiPlatform\Metadata\ApiResource::class],
        'ApiProperty' => [ApiPlatform\Core\Annotation\ApiProperty::class, ApiPlatform\Metadata\ApiProperty::class],
        'ApiFilter' => [ApiPlatform\Core\Annotation\ApiFilter::class, ApiPlatform\Metadata\ApiFilter::class],
    ];

    if (isset($deprecatedClassesWithoutAliases[$className])) {
        trigger_deprecation('api-platform/core', '2.7', sprintf('The class %s is deprecated, use %s instead.', $className, $deprecatedClassesWithoutAliases[$className]));

        return;
    }

    if (isset($deprecatedClassesWithAliases[$className])) {
        trigger_deprecation('api-platform/core', '2.7', sprintf('The class %s is deprecated, use %s instead.', $className, $deprecatedClassesWithAliases[$className]));

        class_alias($deprecatedClassesWithAliases[$className], $className);

        return;
    }

    if (isset($deprecatedAnnotations[$className])) {
        [$annotationClassName, $attributeClassName] = $deprecatedAnnotations[$className];
        trigger_deprecation('api-platform/core', '2.7', sprintf('The Doctrine annotation %s is deprecated, use the PHP attribute %s instead.', $annotationClassName, $attributeClassName));

        return;
    }

    if (isset($deprecatedInterfaces[$className])) {
        trigger_deprecation('api-platform/core', '2.7', sprintf('The interface %s is deprecated, use %s instead.', $className, $deprecatedInterfaces[$className]));
    }
});
