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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\OperationCollectionMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Resource metadata provider for file configured factories tests.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
abstract class FileConfigurationMetadataFactoryProvider extends TestCase
{
    public function resourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata([new OperationCollectionMetadata('/dummies')]);

        $metadata = [
            'shortName' => 'thedummyshortname',
            'description' => 'Dummy resource',
            'itemOperations' => [
                'my_op_name' => ['method' => 'GET'],
                'my_other_op_name' => ['method' => 'POST'],
            ],
            'collectionOperations' => [
                'my_collection_op' => ['method' => 'POST', 'path' => 'the/collection/path'],
            ],
            'subresourceOperations' => [
                'my_collection_subresource' => ['path' => 'the/subresource/path'],
            ],
            'graphql' => [
                'query' => [
                    'normalization_context' => [
                        AbstractNormalizer::GROUPS => ['graphql'],
                    ],
                ],
            ],
            'iri' => 'someirischema',
            'attributes' => [
                'normalization_context' => [
                    AbstractNormalizer::GROUPS => ['default'],
                ],
                'denormalization_context' => [
                    AbstractNormalizer::GROUPS => ['default'],
                ],
                'hydra_context' => [
                    '@type' => 'hydra:Operation',
                    '@hydra:title' => 'File config Dummy',
                ],
                'stateless' => true,
            ],
        ];

        foreach (['shortName', 'description', 'itemOperations', 'collectionOperations', 'subresourceOperations', 'graphql', 'iri', 'attributes'] as $property) {
            $wither = 'with'.ucfirst($property);
            $resourceMetadata = $resourceMetadata->{$wither}($metadata[$property]);
        }

        return [[$resourceMetadata]];
    }

    public function optionalResourceMetadataProvider()
    {
        return [[new ResourceMetadata([(new OperationCollectionMetadata('/dummies'))->withItemOperations(['my_op_name' => ['method' => 'POST']])])]];
    }

    public function noCollectionOperationsResourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata([(new OperationCollectionMetadata('/dummies'))
            ->withItemOperations(['my_op_name' => ['method' => 'POST']])
            ->withCollectionOperations([]),
        ]);

        return [[$resourceMetadata]];
    }

    public function noItemOperationsResourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata([(new OperationCollectionMetadata('/dummies'))
            ->withCollectionOperations(['my_op_name' => ['method' => 'POST']])
            ->withItemOperations([]),
        ]);

        return [[$resourceMetadata]];
    }

    public function legacyOperationsResourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata([(new OperationCollectionMetadata('/dummies'))
            ->withItemOperations([
                'my_op_name' => ['method' => 'POST'],
                'my_other_op_name' => ['method' => 'GET'],
            ])
            ->withCollectionOperations([
                'my_op_name' => ['method' => 'POST'],
            ]),
        ]);

        return [[$resourceMetadata]];
    }
}
