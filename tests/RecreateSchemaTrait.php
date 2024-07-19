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

namespace ApiPlatform\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

trait RecreateSchemaTrait
{
    /**
     * @param class-string[] $classes
     */
    private function recreateSchema(array $classes = []): void
    {
        $manager = $this->getManager();

        if ($manager instanceof DocumentManager) {
            $schemaManager = $manager->getSchemaManager();
            foreach ($classes as $c) {
                $class = str_contains($c, 'Entity') ? str_replace('Entity', 'Document', $c) : $c;
                $schemaManager->dropDocumentDatabase($class);
            }

            return;
        }

        /** @var ClassMetadata[] $cl */
        $cl = [];
        foreach ($classes as $c) {
            $cl[] = $manager->getMetadataFactory()->getMetadataFor($c);
        }

        $schemaTool = new SchemaTool($manager);
        @$schemaTool->dropSchema($cl);
        @$schemaTool->createSchema($cl);
    }

    private function isMongoDB(): bool
    {
        return 'mongodb' === static::getContainer()->getParameter('kernel.environment');
    }

    private function getManager(): EntityManagerInterface|DocumentManager
    {
        return static::getContainer()->get($this->isMongoDB() ? 'doctrine_mongodb' : 'doctrine')->getManager();
    }
}
