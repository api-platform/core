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

return [
    ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface::class => ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface::class,
    ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface::class => ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface::class,
    ApiPlatform\Core\Metadata\Extractor\ExtractorInterface::class => ApiPlatform\Metadata\Extractor\ResourceExtractorInterface::class,

    // Bridge\Doctrine => Doctrine
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\DateFilterInterface::class => ApiPlatform\Doctrine\Common\Filter\DateFilterInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\ExistsFilterInterface::class => ApiPlatform\Doctrine\Common\Filter\ExistsFilterInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\OrderFilterInterface::class => ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\RangeFilterInterface::class => ApiPlatform\Doctrine\Common\Filter\RangeFilterInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface::class => ApiPlatform\Doctrine\Common\Filter\SearchFilterInterface::class,

    // Bridge\Doctrine\MongoDbOdm => Doctrine\Odm
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationCollectionExtensionInterface::class => ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationItemExtensionInterface::class => ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultCollectionExtensionInterface::class => ApiPlatform\Doctrine\Odm\Extension\AggregationResultCollectionExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultItemExtensionInterface::class => ApiPlatform\Doctrine\Odm\Extension\AggregationResultItemExtensionInterface::class,

    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\FilterInterface::class => ApiPlatform\Doctrine\Odm\Filter\FilterInterface::class,

    // Bridge\Doctrine\Orm => Doctrine\Orm
    ApiPlatform\Core\Bridge\Doctrine\Orm\QueryAwareInterface::class => ApiPlatform\Doctrine\Orm\QueryAwareInterface::class,

    ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface::class => ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface::class,

    // Bridge\Elasticsearch => Elasticsearch
    ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface::class => ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface::class,

    // Bridge\Symfony\Validator
    ApiPlatform\Core\Bridge\Symfony\Validator\ValidationGroupsGeneratorInterface::class => ApiPlatform\Symfony\Validator\ValidationGroupsGeneratorInterface::class,
    ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface::class => ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface::class,
    ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRestrictionMetadataInterface::class => ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRestrictionMetadataInterface::class,

    // DataProvider => State/Pagination
    ApiPlatform\Core\DataProvider\PaginatorInterface::class => ApiPlatform\State\Pagination\PaginatorInterface::class,
    ApiPlatform\Core\DataProvider\PartialPaginatorInterface::class => ApiPlatform\State\Pagination\PartialPaginatorInterface::class,

    // Documentation
    ApiPlatform\Core\Documentation\DocumentationInterface::class => ApiPlatform\Documentation\DocumentationInterface::class,

    // JsonLd
    ApiPlatform\Core\JsonLd\AnonymousContextBuilderInterface::class => ApiPlatform\JsonLd\AnonymousContextBuilderInterface::class,
    ApiPlatform\Core\JsonLd\ContextBuilderInterface::class => ApiPlatform\JsonLd\ContextBuilderInterface::class,

    // JsonSchema
    ApiPlatform\Core\JsonSchema\SchemaFactoryInterface::class => ApiPlatform\JsonSchema\SchemaFactoryInterface::class,
    ApiPlatform\Core\JsonSchema\TypeFactoryInterface::class => ApiPlatform\JsonSchema\TypeFactoryInterface::class,

    // OpenApi
    ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface::class => ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface::class,

    // PathResolver
    ApiPlatform\Core\PathResolver\OperationPathResolverInterface::class => ApiPlatform\PathResolver\OperationPathResolverInterface::class,

    // Security => Symfony/Security
    ApiPlatform\Core\Security\ResourceAccessCheckerInterface::class => ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface::class,

    // Serializer
    ApiPlatform\Core\Serializer\Filter\FilterInterface::class => ApiPlatform\Serializer\Filter\FilterInterface::class,
    ApiPlatform\Core\Serializer\SerializerContextBuilderInterface::class => ApiPlatform\Serializer\SerializerContextBuilderInterface::class,

    ApiPlatform\Core\Validator\ValidatorInterface::class => ApiPlatform\Validator\ValidatorInterface::class,

    // API:
    ApiPlatform\Core\Api\ResourceClassResolverInterface::class => ApiPlatform\Api\ResourceClassResolverInterface::class,
    ApiPlatform\Core\Api\UrlGeneratorInterface::class => ApiPlatform\Api\UrlGeneratorInterface::class,

    // GraphQl
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

    ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface::class => ApiPlatform\Operation\PathSegmentNameGeneratorInterface::class,
];
