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

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Resource metadata provider for file configured factories tests.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
abstract class FileConfigurationMetadataFactoryProvider extends \PHPUnit_Framework_TestCase
{
    public function resourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata();

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
            'iri' => 'someirischema',
            'attributes' => [
                'normalization_context' => [
                    'groups' => ['default'],
                ],
                'denormalization_context' => [
                    'groups' => ['default'],
                ],
                'hydra_context' => [
                    '@type' => 'hydra:Operation',
                    '@hydra:title' => 'File config Dummy',
                ],
            ],
        ];

        foreach (['shortName', 'description', 'itemOperations', 'collectionOperations', 'iri', 'attributes'] as $property) {
            $wither = 'with'.ucfirst($property);
            $resourceMetadata = $resourceMetadata->$wither($metadata[$property]);
        }

        return [[$resourceMetadata]];
    }

    public function optionalResourceMetadataProvider()
    {
        $resourceMetadata = new ResourceMetadata();
        $resourceMetadata = $resourceMetadata->withItemOperations(['my_op_name' => ['method' => 'POST']]);

        return [[$resourceMetadata]];
    }
}
