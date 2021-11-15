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
    // Bridge\Doctrine
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\DateFilterInterface::class => ApiPlatform\Doctrine\Common\Filter\DateFilterInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\ExistsFilterInterface::class => ApiPlatform\Doctrine\Common\Filter\ExistsFilterInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\OrderFilterInterface::class => ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\RangeFilterInterface::class => ApiPlatform\Doctrine\Common\Filter\RangeFilterInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface::class => ApiPlatform\Doctrine\Common\Filter\SearchFilterInterface::class,

    // Bridge\Doctrine\MongoDbOdm
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationCollectionExtensionInterface::class => ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationItemExtensionInterface::class => ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultCollectionExtensionInterface::class => ApiPlatform\Doctrine\Odm\Extension\AggregationResultCollectionExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultItemExtensionInterface::class => ApiPlatform\Doctrine\Odm\Extension\AggregationResultItemExtensionInterface::class,

    ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\FilterInterface::class => ApiPlatform\Doctrine\Odm\Filter\FilterInterface::class,

    // Bridge\Doctrine\Orm
    ApiPlatform\Core\Bridge\Doctrine\Orm\QueryAwareInterface::class => ApiPlatform\Doctrine\Orm\QueryAwareInterface::class,

    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryCollectionExtensionInterface::class => ApiPlatform\Doctrine\Orm\Extension\ContextAwareQueryCollectionExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryResultCollectionExtensionInterface::class => ApiPlatform\Doctrine\Orm\Extension\ContextAwareQueryResultCollectionExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\ContextAwareQueryResultItemExtensionInterface::class => ApiPlatform\Doctrine\Orm\Extension\ContextAwareQueryResultItemExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface::class => ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface::class => ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface::class => ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultItemExtensionInterface::class => ApiPlatform\Doctrine\Orm\Extension\QueryResultItemExtensionInterface::class,

    ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ContextAwareFilterInterface::class => ApiPlatform\Doctrine\Orm\Filter\ContextAwareFilterInterface::class,
    ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\FilterInterface::class => ApiPlatform\Doctrine\Orm\Filter\FilterInterface::class,

    ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface::class => ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface::class,

    // Bridge\Elasticsearch
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\RequestBodySearchCollectionExtensionInterface::class => ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface::class,

    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\ConstantScoreFilterExtension::class => ApiPlatform\Elasticsearch\Filter\ConstantScoreFilterInterface::class,
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\FilterInterface::class => ApiPlatform\Elasticsearch\Filter\FilterInterface::class,
    ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\SortFilterInterface::class => ApiPlatform\Elasticsearch\Filter\SortFilterInterface::class,

    ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface::class => ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface::class,

    ApiPlatform\Core\DataTransformer\DataTransformerInitializerInterface::class => ApiPlatform\DataTransformer\DataTransformerInitializerInterface::class,
    ApiPlatform\Core\DataTransformer\DataTransformerInterface::class => ApiPlatform\DataTransformer\DataTransformerInterface::class,

    ApiPlatform\Core\Validator\ValidatorInterface::class => ApiPlatform\Symfony\EventListener\ValidatorInterface::class,
];
