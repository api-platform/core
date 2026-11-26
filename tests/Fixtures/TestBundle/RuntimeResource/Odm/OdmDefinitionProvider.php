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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Odm;

use ApiPlatform\Tests\Fixtures\TestBundle\Document\RuntimeResource\Generation;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RuntimeResource\ResourceDefinition;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Attribute;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\AttributeType;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Definition;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\DefinitionProvider;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use MongoDB\Operation\FindOneAndUpdate;

final class OdmDefinitionProvider implements DefinitionProvider
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    public function definitions(): iterable
    {
        foreach ($this->manager()->getDocumentCollection(ResourceDefinition::class)->find([], ['sort' => ['_id' => 1], 'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]) as $row) {
            $attributes = [];
            foreach ($row['attributes'] ?? [] as $attribute) {
                if (null !== $type = AttributeType::tryFrom($attribute['type'])) {
                    $attributes[$attribute['name']] = new Attribute($attribute['name'], $type, $attribute['required'], $attribute['collection'], $attribute['options'] ?? [], $attribute['filterable']);
                }
            }

            yield new Definition($row['name'], $row['label'], $row['security'] ?? null, $attributes);
        }
    }

    public function generation(): int
    {
        return (int) ($this->manager()->getDocumentCollection(Generation::class)->findOne(['_id' => 1])['value'] ?? 0);
    }

    public function publish(?callable $changes = null): int
    {
        $manager = $this->manager();
        if (null !== $changes) {
            $changes($manager);
            $manager->flush();
        }

        return (int) $manager->getDocumentCollection(Generation::class)->findOneAndUpdate(['_id' => 1], ['$inc' => ['value' => 1]], ['upsert' => true, 'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER])['value'];
    }

    private function manager(): DocumentManager
    {
        $manager = $this->registry->getManagerForClass(Generation::class);
        \assert($manager instanceof DocumentManager);

        return $manager;
    }
}
