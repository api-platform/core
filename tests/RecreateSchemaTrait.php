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

        /** @var ClassMetadata[] $metadataCollection */
        $metadataCollection = [];
        $processedClasses = [];

        foreach ($classes as $class) {
            $this->addMetadataWithDependencies($manager, $class, $metadataCollection, $processedClasses);
        }

        $schemaTool = new SchemaTool($manager);

        @$schemaTool->dropDatabase();
        @$schemaTool->createSchema($metadataCollection);
    }

    /**
     * @param array<ClassMetadata> $metadataCollection
     * @param array<string, bool>  $processedClasses
     *
     * @param-out array<ClassMetadata> $metadataCollection
     * @param-out array<string, bool>  $processedClasses
     */
    private function addMetadataWithDependencies(EntityManagerInterface $manager, string $class, array &$metadataCollection, array &$processedClasses): void
    {
        if (isset($processedClasses[$class])) {
            return;
        }

        $metadata = $manager->getMetadataFactory()->getMetadataFor($class);
        $metadataCollection[] = $metadata;
        $processedClasses[$class] = true;

        foreach ($metadata->getAssociationMappings() as $associationMapping) {
            $this->addMetadataWithDependencies($manager, $associationMapping->targetEntity, $metadataCollection, $processedClasses);
        }
    }

    private function isMongoDB(): bool
    {
        return 'mongodb' === static::getContainer()->getParameter('kernel.environment');
    }

    private function isPostgres(): bool
    {
        return 'postgres' === static::getContainer()->getParameter('kernel.environment');
    }

    private function isSqlite(): bool
    {
        return \in_array(static::getContainer()->getParameter('kernel.environment'), ['sqlite', 'test'], true);
    }

    private function getManager(): EntityManagerInterface|DocumentManager
    {
        return static::getContainer()->get($this->isMongoDB() ? 'doctrine_mongodb' : 'doctrine')->getManager();
    }
}
