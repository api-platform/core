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

namespace ApiPlatform\Core\Test;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

/**
 * Provides utility functions needed in tests.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class DoctrineMongoDbOdmTestHelper
{
    /**
     * Returns a document manager for testing.
     */
    public static function createTestDocumentManager(?Configuration $config = null): DocumentManager
    {
        if (null === $config) {
            $config = self::createTestConfiguration();
        }

        $connection = new Connection();

        return DocumentManager::create($connection, $config);
    }

    public static function createTestConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setDocumentNamespaces(['SymfonyTestsDoctrine' => 'Symfony\Bridge\Doctrine\Tests\Fixtures']);
        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(\sys_get_temp_dir());
        $config->setProxyNamespace('SymfonyTests\Doctrine');
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        $config->setMetadataCacheImpl(new ArrayCache());
        $config->setHydratorDir(\sys_get_temp_dir().'/Hydrators');
        $config->setHydratorNamespace('Hydrators');

        return $config;
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
