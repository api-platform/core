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
        ApiPlatform\Core\GraphQl\Type\SchemaBuilderInterface::class => ApiPlatform\GraphQl\Type\SchemaBuilderInterface::class,
        ApiPlatform\Core\GraphQl\Type\FieldsBuilderInterface::class => ApiPlatform\GraphQl\Type\FieldsBuilderInterface::class,
        ApiPlatform\Core\GraphQl\Type\Definition\TypeInterface::class => ApiPlatform\GraphQl\Type\Definition\TypeInterface::class,
        ApiPlatform\Core\GraphQl\Type\TypeConverterInterface::class => ApiPlatform\GraphQl\Type\TypeConverterInterface::class,
        ApiPlatform\Core\GraphQl\Type\TypeBuilderInterface::class => ApiPlatform\GraphQl\Type\TypeBuilderInterface::class,
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
        ApiPlatform\Core\GraphQl\Type\FieldsBuilder::class => ApiPlatform\GraphQl\Type\FieldsBuilder::class,
        ApiPlatform\Core\GraphQl\Type\TypeNotFoundException::class => ApiPlatform\GraphQl\Type\TypeNotFoundException::class,
        ApiPlatform\Core\GraphQl\Type\TypeConverter::class => ApiPlatform\GraphQl\Type\TypeConverter::class,
        ApiPlatform\Core\GraphQl\Type\TypesFactory::class => ApiPlatform\GraphQl\Type\TypesFactory::class,
        ApiPlatform\Core\GraphQl\Type\Definition\UploadType::class => ApiPlatform\GraphQl\Type\Definition\UploadType::class,
        ApiPlatform\Core\GraphQl\Type\Definition\IterableType::class => ApiPlatform\GraphQl\Type\Definition\IterableType::class,
        ApiPlatform\Core\GraphQl\Type\SchemaBuilder::class => ApiPlatform\GraphQl\Type\SchemaBuilder::class,
        ApiPlatform\Core\GraphQl\Type\TypesContainer::class => ApiPlatform\GraphQl\Type\TypesContainer::class,
        ApiPlatform\Core\GraphQl\Type\TypeBuilder::class => ApiPlatform\GraphQl\Type\TypeBuilder::class,
    ];

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
});
