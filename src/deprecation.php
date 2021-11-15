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

spl_autoload_register(function ($className) {
    // We can not class alias when working with doctrine annotations
    static $deprecatedAnnotations = [
        'ApiResource' => [ApiPlatform\Core\Annotation\ApiResource::class, ApiPlatform\Metadata\ApiResource::class],
        'ApiProperty' => [ApiPlatform\Core\Annotation\ApiProperty::class, ApiPlatform\Metadata\ApiProperty::class],
    ];

    static $deprecatedClasses = [
        ApiPlatform\Core\Api\UrlGeneratorInterface::class => ApiPlatform\Api\UrlGeneratorInterface::class,
        ApiPlatform\Core\Annotation\ApiResource::class => ApiPlatform\Metadata\ApiResource::class,
        ApiPlatform\Core\Annotation\ApiProperty::class => ApiPlatform\Metadata\ApiProperty::class,
        ApiPlatform\Core\Metadata\Property\Factory\SerializerPropertyMetadataFactory::class => ApiPlatform\Metadata\Property\Factory\SerializerPropertyMetadataFactory::class,
        ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface::class => ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface::class,

        ApiPlatform\Core\Metadata\Property\Factory\CachedPropertyMetadataFactory::class => ApiPlatform\Metadata\Property\Factory\CachedPropertyMetadataFactory::class,
        ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyMetadataFactory::class => ApiPlatform\Metadata\Property\Factory\ExtractorPropertyMetadataFactory::class,
        ApiPlatform\Core\Metadata\Property\Factory\DefaultPropertyMetadataFactory::class => ApiPlatform\Metadata\Property\Factory\DefaultPropertyMetadataFactory::class,

        ApiPlatform\Core\Metadata\Property\Factory\CachedPropertyNameCollectionFactory::class => ApiPlatform\Metadata\Property\Factory\CachedPropertyNameCollectionFactory::class,
        ApiPlatform\Core\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory::class => ApiPlatform\Metadata\Property\Factory\ExtractorPropertyNameCollectionFactory::class,
        // ApiPlatform\Core\Metadata\Extractor\ExtractorInterface::class => ApiPlatform\Metadata\Extractor\ExtractorInterface::class,
        // ApiPlatform\Core\Metadata\Extractor\AbstractExtractor::class => ApiPlatform\Metadata\Extractor\AbstractExtractor::class,
        // ApiPlatform\Core\Metadata\Extractor\XmlExtractor::class => ApiPlatform\Metadata\Extractor\XmlExtractor::class,
        // ApiPlatform\Core\Metadata\Extractor\YamlExtractor::class => ApiPlatform\Metadata\Extractor\YamlExtractor::class,

        ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface::class => ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface::class,
        ApiPlatform\Core\Metadata\Property\PropertyNameCollection::class => ApiPlatform\Metadata\Property\PropertyNameCollection::class,

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

        // GraphQl
        ApiPlatform\Core\GraphQl\Executor::class => ApiPlatform\GraphQl\Executor::class,
        ApiPlatform\Core\GraphQl\ExecutorInterface::class => ApiPlatform\GraphQl\ExecutorInterface::class,
        ApiPlatform\Core\GraphQl\Error\ErrorHandlerInterface::class => ApiPlatform\GraphQl\Error\ErrorHandlerInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\ValidateStageInterface::class => ApiPlatform\GraphQl\Resolver\Stage\ValidateStageInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\ReadStageInterface::class => ApiPlatform\GraphQl\Resolver\Stage\ReadStageInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityPostDenormalizeStageInterface::class => ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStageInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityStageInterface::class => ApiPlatform\GraphQl\Resolver\Stage\SecurityStageInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\WriteStageInterface::class => ApiPlatform\GraphQl\Resolver\Stage\WriteStageInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStageInterface::class => ApiPlatform\GraphQl\Resolver\Stage\SerializeStageInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\DeserializeStageInterface::class => ApiPlatform\GraphQl\Resolver\Stage\DeserializeStageInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface::class => ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface::class => ApiPlatform\GraphQl\Resolver\QueryCollectionResolverInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\Factory\ResolverFactoryInterface::class => ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface::class,
        ApiPlatform\Core\GraphQl\Resolver\MutationResolverInterface::class => ApiPlatform\GraphQl\Resolver\MutationResolverInterface::class,
        ApiPlatform\Core\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface::class => ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface::class,
        ApiPlatform\Core\GraphQl\Subscription\SubscriptionIdentifierGeneratorInterface::class => ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGeneratorInterface::class,
        ApiPlatform\Core\GraphQl\Subscription\SubscriptionManagerInterface::class => ApiPlatform\GraphQl\Subscription\SubscriptionManagerInterface::class,
        ApiPlatform\Core\GraphQl\Serializer\SerializerContextBuilderInterface::class => ApiPlatform\GraphQl\Serializer\SerializerContextBuilderInterface::class,
        ApiPlatform\Core\GraphQl\Type\TypesFactoryInterface::class => ApiPlatform\GraphQl\Type\TypesFactoryInterface::class,
        ApiPlatform\Core\GraphQl\Type\Definition\TypeInterface::class => ApiPlatform\GraphQl\Type\Definition\TypeInterface::class,
        ApiPlatform\Core\GraphQl\Type\TypesContainerInterface::class => ApiPlatform\GraphQl\Type\TypesContainerInterface::class,
        ApiPlatform\Core\GraphQl\Error\ErrorHandler::class => ApiPlatform\GraphQl\Error\ErrorHandler::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\ValidateStage::class => ApiPlatform\GraphQl\Resolver\Stage\ValidateStage::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\ReadStage::class => ApiPlatform\GraphQl\Resolver\Stage\ReadStage::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityPostDenormalizeStage::class => ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStage::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\SecurityStage::class => ApiPlatform\GraphQl\Resolver\Stage\SecurityStage::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\WriteStage::class => ApiPlatform\GraphQl\Resolver\Stage\WriteStage::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\DeserializeStage::class => ApiPlatform\GraphQl\Resolver\Stage\DeserializeStage::class,
        ApiPlatform\Core\GraphQl\Resolver\Stage\SerializeStage::class => ApiPlatform\GraphQl\Resolver\Stage\SerializeStage::class,
        ApiPlatform\Core\GraphQl\Resolver\ResourceFieldResolver::class => ApiPlatform\GraphQl\Resolver\ResourceFieldResolver::class,
        ApiPlatform\Core\GraphQl\Resolver\Util\IdentifierTrait::class => ApiPlatform\GraphQl\Resolver\Util\IdentifierTrait::class,
        ApiPlatform\Core\GraphQl\Resolver\Factory\ItemResolverFactory::class => ApiPlatform\GraphQl\Resolver\Factory\ItemResolverFactory::class,
        ApiPlatform\Core\GraphQl\Resolver\Factory\ItemSubscriptionResolverFactory::class => ApiPlatform\GraphQl\Resolver\Factory\ItemSubscriptionResolverFactory::class,
        ApiPlatform\Core\GraphQl\Resolver\Factory\CollectionResolverFactory::class => ApiPlatform\GraphQl\Resolver\Factory\CollectionResolverFactory::class,
        ApiPlatform\Core\GraphQl\Action\EntrypointAction::class => ApiPlatform\GraphQl\Action\EntrypointAction::class,
        ApiPlatform\Core\GraphQl\Action\GraphiQlAction::class => ApiPlatform\GraphQl\Action\GraphiQlAction::class,
        ApiPlatform\Core\GraphQl\Subscription\MercureSubscriptionIriGenerator::class => ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGenerator::class,
        ApiPlatform\Core\GraphQl\Subscription\SubscriptionIdentifierGenerator::class => ApiPlatform\GraphQl\Subscription\SubscriptionIdentifierGenerator::class,
        ApiPlatform\Core\GraphQl\Subscription\SubscriptionManager::class => ApiPlatform\GraphQl\Subscription\SubscriptionManager::class,
        ApiPlatform\Core\GraphQl\Action\GraphQlPlaygroundAction::class => ApiPlatform\GraphQl\Action\GraphQlPlaygroundAction::class,
        ApiPlatform\Core\GraphQl\Resolver\Factory\ItemMutationResolverFactory::class => ApiPlatform\GraphQl\Resolver\Factory\ItemMutationResolverFactory::class,
        ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer::class => ApiPlatform\GraphQl\Serializer\ItemNormalizer::class,
        ApiPlatform\Core\GraphQl\Serializer\Exception\ErrorNormalizer::class => ApiPlatform\GraphQl\Serializer\Exception\ErrorNormalizer::class,
        ApiPlatform\Core\GraphQl\Serializer\Exception\ValidationExceptionNormalizer::class => ApiPlatform\GraphQl\Serializer\Exception\ValidationExceptionNormalizer::class,
        ApiPlatform\Core\GraphQl\Serializer\Exception\HttpExceptionNormalizer::class => ApiPlatform\GraphQl\Serializer\Exception\HttpExceptionNormalizer::class,
        ApiPlatform\Core\GraphQl\Serializer\Exception\RuntimeExceptionNormalizer::class => ApiPlatform\GraphQl\Serializer\Exception\RuntimeExceptionNormalizer::class,
        ApiPlatform\Core\GraphQl\Serializer\ObjectNormalizer::class => ApiPlatform\GraphQl\Serializer\ObjectNormalizer::class,
        ApiPlatform\Core\GraphQl\Serializer\SerializerContextBuilder::class => ApiPlatform\GraphQl\Serializer\SerializerContextBuilder::class,
        ApiPlatform\Core\GraphQl\Type\TypeNotFoundException::class => ApiPlatform\GraphQl\Type\TypeNotFoundException::class,
        ApiPlatform\Core\GraphQl\Type\TypesFactory::class => ApiPlatform\GraphQl\Type\TypesFactory::class,
        ApiPlatform\Core\GraphQl\Type\Definition\UploadType::class => ApiPlatform\GraphQl\Type\Definition\UploadType::class,
        ApiPlatform\Core\GraphQl\Type\Definition\IterableType::class => ApiPlatform\GraphQl\Type\Definition\IterableType::class,
        ApiPlatform\Core\GraphQl\Type\TypesContainer::class => ApiPlatform\GraphQl\Type\TypesContainer::class,

        // Action
        ApiPlatform\Core\Action\EntrypointAction::class => ApiPlatform\Action\EntrypointAction::class,
        ApiPlatform\Core\Action\ExceptionAction::class => ApiPlatform\Action\ExceptionAction::class,
        ApiPlatform\Core\Action\NotFoundAction::class => ApiPlatform\Action\NotFoundAction::class,
        ApiPlatform\Core\Action\PlaceholderAction::class => ApiPlatform\Action\PlaceholderAction::class,

        // Bridge\Doctrine
        ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait::class => ApiPlatform\Doctrine\Common\PropertyHelperTrait::class,

        ApiPlatform\Core\Bridge\Doctrine\Common\Filter\BooleanFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\BooleanFilterTrait::class,
        ApiPlatform\Core\Bridge\Doctrine\Common\Filter\DateFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\DateFilterTrait::class,
        ApiPlatform\Core\Bridge\Doctrine\Common\Filter\ExistsFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\ExistsFilterTrait::class,
        ApiPlatform\Core\Bridge\Doctrine\Common\Filter\NumericFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\NumericFilterTrait::class,
        ApiPlatform\Core\Bridge\Doctrine\Common\Filter\OrderFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\OrderFilterTrait::class,
        ApiPlatform\Core\Bridge\Doctrine\Common\Filter\RangeFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\RangeFilterTrait::class,
        ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterTrait::class => ApiPlatform\Doctrine\Common\Filter\SearchFilterTrait::class,

        // Bridge\Doctrine\EventListener
        ApiPlatform\Core\Bridge\Doctrine\EventListener\PublishMercureUpdatesListener::class => ApiPlatform\Doctrine\EventListener\PublishMercureUpdatesListener::class,
        ApiPlatform\Core\Bridge\Doctrine\EventListener\PurgeHttpCacheListener::class => ApiPlatform\Doctrine\EventListener\PurgeHttpCacheListener::class,
        ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener::class => ApiPlatform\Doctrine\EventListener\WriteListener::class,

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

        ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Metadata\Property\DoctrineMongoDbOdmPropertyMetadataFactory::class => ApiPlatform\Doctrine\Odm\Metadata\Property\DoctrineMongoDbOdmPropertyMetadataFactory::class,

        ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\PropertyInfo\DoctrineExtractor::class => ApiPlatform\Doctrine\Odm\PropertyInfo\DoctrineExtractor::class,

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

        ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter::class => ApiPlatform\Doctrine\Orm\Filter\AbstractContextAwareFilter::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter::class => ApiPlatform\Doctrine\Orm\Filter\AbstractFilter::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter::class => ApiPlatform\Doctrine\Orm\Filter\BooleanFilter::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter::class => ApiPlatform\Doctrine\Orm\Filter\DateFilter::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter::class => ApiPlatform\Doctrine\Orm\Filter\ExistsFilter::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter::class => ApiPlatform\Doctrine\Orm\Filter\NumericFilter::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter::class => ApiPlatform\Doctrine\Orm\Filter\OrderFilter::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter::class => ApiPlatform\Doctrine\Orm\Filter\RangeFilter::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter::class => ApiPlatform\Doctrine\Orm\Filter\SearchFilter::class,

        ApiPlatform\Core\Bridge\Doctrine\Orm\Util\EagerLoadingTrait::class => ApiPlatform\Doctrine\Orm\Util\EagerLoadingTrait::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper::class => ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryChecker::class => ApiPlatform\Doctrine\Orm\Util\QueryChecker::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryJoinParser::class => ApiPlatform\Doctrine\Orm\Util\QueryJoinParser::class,
        ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator::class => ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator::class,

        ApiPlatform\Core\Bridge\Doctrine\Orm\Metadata\Property\DoctrineOrmPropertyMetadataFactory::class => ApiPlatform\Doctrine\Orm\Metadata\Property\DoctrineOrmPropertyMetadataFactory::class,

        // Bridge\Elasticsearch
        ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException::class => ApiPlatform\Elasticsearch\Exception\IndexNotFoundException::class,
        ApiPlatform\Core\Bridge\Elasticsearch\Exception\NonUniqueIdentifierException::class => ApiPlatform\Elasticsearch\Exception\NonUniqueIdentifierException::class,

        ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\AbstractFilterExtension::class => ApiPlatform\Elasticsearch\Extension\AbstractFilterExtension::class,
        ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\ConstantScoreFilterExtension::class => ApiPlatform\Elasticsearch\Extension\ConstantScoreFilterExtension::class,
        ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\SortExtension::class => ApiPlatform\Elasticsearch\Extension\SortExtension::class,
        ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\SortFilterExtension::class => ApiPlatform\Elasticsearch\Extension\SortFilterExtension::class,

        ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\AbstractFilter::class => ApiPlatform\Elasticsearch\Filter\AbstractFilter::class,
        ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\AbstractSearchFilter::class => ApiPlatform\Elasticsearch\Filter\AbstractSearchFilter::class,
        ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\MatchFilter::class => ApiPlatform\Elasticsearch\Filter\MatchFilter::class,
        ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\OrderFilter::class => ApiPlatform\Elasticsearch\Filter\OrderFilter::class,
        ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\TermFilter::class => ApiPlatform\Elasticsearch\Filter\TermFilter::class,

        ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\AttributeDocumentMetadataFactory::class => ApiPlatform\Elasticsearch\Metadata\Document\Factory\AttributeDocumentMetadataFactory::class,
        ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\CachedDocumentMetadataFactory::class => ApiPlatform\Elasticsearch\Metadata\Document\Factory\CachedDocumentMetadataFactory::class,
        ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\CatDocumentMetadataFactory::class => ApiPlatform\Elasticsearch\Metadata\Document\Factory\CatDocumentMetadataFactory::class,
        ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\ConfiguredDocumentMetadataFactory::class => ApiPlatform\Elasticsearch\Metadata\Document\Factory\ConfiguredDocumentMetadataFactory::class,
        ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata::class => ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata::class,

        ApiPlatform\Core\Bridge\Elasticsearch\Serializer\NameConverter\InnerFieldsNameConverter::class => ApiPlatform\Elasticsearch\Serializer\NameConverter\InnerFieldsNameConverter::class,
        ApiPlatform\Core\Bridge\Elasticsearch\Serializer\DocumentNormalizer::class => ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer::class,
        ApiPlatform\Core\Bridge\Elasticsearch\Serializer\ItemNormalizer::class => ApiPlatform\Elasticsearch\Serializer\ItemNormalizer::class,
        ApiPlatform\Core\Bridge\Elasticsearch\Util\FieldDatatypeTrait::class => ApiPlatform\Elasticsearch\Util\FieldDatatypeTrait::class,

        ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Paginator::class => ApiPlatform\Elasticsearch\Paginator::class,

        // Bridge\RamseyUuid
        ApiPlatform\Core\Bridge\RamseyUuid\Serializer\UuidDenormalizer::class => ApiPlatform\RamseyUuid\Serializer\UuidDenormalizer::class,

        // Cache
        ApiPlatform\Core\Cached\CachedTrait::class => ApiPlatform\Util\CachedTrait::class,

        // DataProvider => State/Pagination
        ApiPlatform\Core\DataProvider\ArrayPaginator::class => ApiPlatform\State\Pagination\ArrayPaginator::class,
        ApiPlatform\Core\DataProvider\Pagination::class => ApiPlatform\State\Pagination\Pagination::class,
        ApiPlatform\Core\DataProvider\PaginationOptions::class => ApiPlatform\State\Pagination\PaginationOptions::class,
        ApiPlatform\Core\DataProvider\TraversablePaginator::class => ApiPlatform\State\Pagination\TraversablePaginator::class,

        // Util
        ApiPlatform\Core\Util\AnnotationFilterExtractorTrait::class => ApiPlatform\Util\AnnotationFilterExtractorTrait::class,
        ApiPlatform\Core\Util\ArrayTrait::class => ApiPlatform\Util\ArrayTrait::class,
        ApiPlatform\Core\Util\AttributesExtractor::class => ApiPlatform\Util\AttributesExtractor::class,
        ApiPlatform\Core\Util\ClassInfoTrait::class => ApiPlatform\Util\ClassInfoTrait::class,
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
        ApiPlatform\Core\Util\SortTrait::class => ApiPlatform\Util\SortTrait::class,

        // Validator
        ApiPlatform\Core\Validator\EventListener\ValidateListener::class => ApiPlatform\Symfony\EventListener\ValidateListener::class,
        ApiPlatform\Core\Validator\Exception\ValidationException::class => ApiPlatform\Symfony\EventListener\Exception\ValidationException::class,
    ];

    $deprecatedInterfaces = include 'deprecated_interfaces.php';

    if (ApiPlatform\Core\Metadata\Property\PropertyMetadata::class === $className) {
        trigger_deprecation('api-platform/core', '2.7', sprintf('The class %s is deprecated, use %s instead.', $className, ApiPlatform\Metadata\ApiProperty::class));
    }

    // No class alias
    if (isset($deprecatedClasses[$className])) {
        class_alias($deprecatedClasses[$className], $className);
        trigger_deprecation('api-platform/core', '2.7', sprintf('The class %s is deprecated, use %s instead.', $className, $deprecatedClasses[$className]));

        return;
    }

    if (isset($deprecatedAnnotations[$className])) {
        [$annotationClassName, $attributeClassName] = $deprecatedAnnotations[$className];
        trigger_deprecation('api-platform/core', '2.7', sprintf('The Doctrine annotation %s is deprecated, use the PHP attribute %s instead.', $annotationClassName, $attributeClassName));

        return;
    }

    if (isset($deprecatedInterfaces[$className])) {
        class_alias($deprecatedInterfaces[$className], $className);
        trigger_deprecation('api-platform/core', '2.7', sprintf('The interface %s is deprecated, use %s instead.', $className, $deprecatedClasses[$className]));

        return;
    }
});
