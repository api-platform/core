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

$deprecatedClassesWithAliases = [
    ApiPlatform\HttpCache\EventListener\AddHeadersListener::class => ApiPlatform\Symfony\EventListener\AddHeadersListener::class,
    ApiPlatform\HttpCache\EventListener\AddTagsListener::class => ApiPlatform\Symfony\EventListener\AddTagsListener::class,
    ApiPlatform\Exception\FilterValidationException::class => ApiPlatform\ParameterValidator\Exception\ValidationException::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\ArrayItems::class => ApiPlatform\ParameterValidator\Validator\ArrayItems::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\Bounds::class => ApiPlatform\ParameterValidator\Validator\Bounds::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\Enum::class => ApiPlatform\ParameterValidator\Validator\Enum::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\Length::class => ApiPlatform\ParameterValidator\Validator\Length::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\MultipleOf::class => ApiPlatform\ParameterValidator\Validator\MultipleOf::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\Pattern::class => ApiPlatform\ParameterValidator\Validator\Pattern::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\Required::class => ApiPlatform\ParameterValidator\Validator\Required::class,
];

$movedClasses = [
    ApiPlatform\Action\EntrypointAction::class => ApiPlatform\Symfony\Action\EntrypointAction::class,
    ApiPlatform\Action\NotExposedAction::class => ApiPlatform\Symfony\Action\NotExposedAction::class,
    ApiPlatform\Action\NotFoundAction::class => ApiPlatform\Symfony\Action\NotFoundAction::class,
    ApiPlatform\Action\PlaceholderAction::class => ApiPlatform\Symfony\Action\PlaceholderAction::class,
    ApiPlatform\Api\Entrypoint::class => ApiPlatform\Documentation\Entrypoint::class,
    ApiPlatform\Api\UrlGeneratorInterface::class => ApiPlatform\Metadata\UrlGeneratorInterface::class,
    ApiPlatform\Exception\ExceptionInterface::class => ApiPlatform\Metadata\Exception\ExceptionInterface::class,
    ApiPlatform\Exception\InvalidArgumentException::class => ApiPlatform\Metadata\Exception\InvalidArgumentException::class,
    ApiPlatform\Exception\InvalidIdentifierException::class => ApiPlatform\Metadata\Exception\InvalidIdentifierException::class,
    ApiPlatform\Exception\InvalidUriVariableException::class => ApiPlatform\Metadata\Exception\InvalidUriVariableException::class,
    ApiPlatform\Exception\ItemNotFoundException::class => ApiPlatform\Metadata\Exception\ItemNotFoundException::class,
    ApiPlatform\Exception\NotExposedHttpException::class => ApiPlatform\Metadata\Exception\NotExposedHttpException::class,
    ApiPlatform\Exception\OperationNotFoundException::class => ApiPlatform\Metadata\Exception\OperationNotFoundException::class,
    ApiPlatform\Exception\PropertyNotFoundException::class => ApiPlatform\Metadata\Exception\PropertyNotFoundException::class,
    ApiPlatform\Exception\ResourceClassNotFoundException::class => ApiPlatform\Metadata\Exception\ResourceClassNotFoundException::class,
    ApiPlatform\Exception\RuntimeException::class => ApiPlatform\Metadata\Exception\RuntimeException::class,
    ApiPlatform\GraphQl\Type\TypeBuilderInterface::class => ApiPlatform\GraphQl\Type\ContextAwareTypeBuilderInterface::class,
    ApiPlatform\GraphQl\Type\TypeBuilderEnumInterface::class => ApiPlatform\GraphQl\Type\ContextAwareTypeBuilderInterface::class,
    ApiPlatform\Operation\DashPathSegmentNameGenerator::class => ApiPlatform\Metadata\Operation\DashPathSegmentNameGenerator::class,
    ApiPlatform\Operation\UnderscorePathSegmentNameGenerator::class => ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator::class,
    ApiPlatform\Operation\PathSegmentNameGeneratorInterface::class => ApiPlatform\Metadata\Operation\PathSegmentNameGeneratorInterface::class,
    ApiPlatform\Symfony\Bundle\Command\OpenApiCommand::class => ApiPlatform\OpenApi\Command\OpenApiCommand::class,
    ApiPlatform\Util\ClientTrait::class => ApiPlatform\Symfony\Bundle\Test\ClientTrait::class,
    ApiPlatform\Util\RequestAttributesExtractor::class => ApiPlatform\State\Util\RequestAttributesExtractor::class,
    ApiPlatform\Symfony\Util\RequestAttributesExtractor::class => ApiPlatform\State\Util\RequestAttributesExtractor::class,
    ApiPlatform\Doctrine\EventListener\PublishMercureUpdatesListener::class => ApiPlatform\Symfony\Doctrine\EventListener\PublishMercureUpdatesListener::class,
    ApiPlatform\Doctrine\EventListener\PurgeHttpCacheListener::class => ApiPlatform\Symfony\Doctrine\EventListener\PurgeHttpCacheListener::class,
];

$removedClasses = [
    ApiPlatform\Action\ExceptionAction::class => true,
    ApiPlatform\Exception\DeserializationException::class => true,
    ApiPlatform\Exception\ErrorCodeSerializableInterface::class => true,
    ApiPlatform\Exception\FilterValidationException::class => true,
    ApiPlatform\Exception\InvalidResourceException::class => true,
    ApiPlatform\Exception\InvalidValueException::class => true,
    ApiPlatform\Exception\ResourceClassNotSupportedException::class => true,
    ApiPlatform\GraphQl\Resolver\Factory\CollectionResolverFactory::class => true,
    ApiPlatform\GraphQl\Resolver\Factory\ItemMutationResolverFactory::class => true,
    ApiPlatform\GraphQl\Resolver\Factory\ItemResolverFactory::class => true,
    ApiPlatform\GraphQl\Resolver\Factory\ItemSubscriptionResolverFactory::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\DeserializeStage::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\DeserializeStageInterface::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\ReadStage::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\ReadStageInterface::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStage::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\SecurityPostDenormalizeStageInterface::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\SecurityPostValidationStage::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\SecurityPostValidationStageInterface::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\SecurityStage::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\SecurityStageInterface::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\SerializeStage::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\SerializeStageInterface::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\ValidateStage::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\ValidateStageInterface::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\WriteStage::class => true,
    ApiPlatform\GraphQl\Resolver\Stage\WriteStageInterface::class => true,
    ApiPlatform\HttpCache\EventListener\AddHeadersListener::class => true,
    ApiPlatform\HttpCache\EventListener\AddTagsListener::class => true,
    ApiPlatform\Hydra\EventListener\AddLinkHeaderListener::class => true,
    ApiPlatform\Hydra\Serializer\ErrorNormalizer::class => true,
    ApiPlatform\JsonSchema\TypeFactory::class => true,
    ApiPlatform\JsonSchema\TypeFactoryInterface::class => true,
    ApiPlatform\Problem\Serializer\ErrorNormalizer::class => true,
    ApiPlatform\Serializer\CacheableSupportsMethodInterface::class => true,
    ApiPlatform\OpenApi\Serializer\CacheableSupportsMethodInterface::class => true,
    ApiPlatform\Symfony\EventListener\AddHeadersListener::class => true,
    ApiPlatform\Symfony\EventListener\AddLinkHeaderListener::class => true,
    ApiPlatform\Symfony\EventListener\AddTagsListener::class => true,
    ApiPlatform\Symfony\EventListener\DenyAccessListener::class => true,
    ApiPlatform\Symfony\EventListener\QueryParameterValidateListener::class => true,
    ApiPlatform\Symfony\Validator\EventListener\ValidationExceptionListener::class => true,
    ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface::class => true,
    ApiPlatform\Symfony\Validator\Exception\ValidationException::class => true,
    ApiPlatform\Symfony\Validator\State\QueryParameterValidateProvider::class => true,
    ApiPlatform\Util\ErrorFormatGuesser::class => true,
];

spl_autoload_register(function ($className) use ($deprecatedClassesWithAliases, $movedClasses, $removedClasses): void {
    if (isset($removedClasses[$className])) {
        trigger_deprecation('api-platform/core', '4.0', sprintf('The class %s is deprecated and will be removed.', $className));

        return;
    }

    if (isset($movedClasses[$className])) {
        trigger_deprecation('api-platform/core', '4.0', sprintf('The class %s is deprecated, use %s instead.', $className, $movedClasses[$className]));

        return;
    }

    if (isset($deprecatedClassesWithAliases[$className])) {
        trigger_deprecation('api-platform/core', '4.0', sprintf('The class %s is deprecated, use %s instead.', $className, $deprecatedClassesWithAliases[$className]));

        class_alias($deprecatedClassesWithAliases[$className], $className);

        return;
    }
});
