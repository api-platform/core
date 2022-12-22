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

namespace ApiPlatform\Test;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Source: https://github.com/doctrine/DoctrineMongoDBBundle/blob/0174003844bc566bb4cb3b7d10c5528d1924d719/Tests/TestCase.php
 * Test got excluded from vendor in 4.x.
 */
class DoctrineMongoDbOdmTestCase extends TestCase
{
    public static function createTestDocumentManager($paths = []): DocumentManager
    {
        $config = new Configuration();
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_FILE_NOT_EXISTS);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setHydratorDir(sys_get_temp_dir());
        $config->setProxyNamespace('SymfonyTests\Doctrine');
        $config->setHydratorNamespace('SymfonyTests\Doctrine');
        $config->setMetadataDriverImpl(new AttributeDriver($paths, new AttributeReader()));
        $config->setMetadataCache(new ArrayAdapter());

        return DocumentManager::create(null, $config);
    }
}
