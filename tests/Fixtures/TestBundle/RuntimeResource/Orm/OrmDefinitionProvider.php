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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Orm;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RuntimeResource\Generation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RuntimeResource\ResourceDefinition;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Attribute;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\AttributeType;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\Definition;
use ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource\DefinitionProvider;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class OrmDefinitionProvider implements DefinitionProvider
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    public function definitions(): iterable
    {
        try {
            $rows = $this->manager()->createQuery(\sprintf('SELECT d, a FROM %s d LEFT JOIN d.attributes a ORDER BY d.id, a.position', ResourceDefinition::class))->getArrayResult();
        } catch (TableNotFoundException) {
            return;
        }

        foreach ($rows as $row) {
            $attributes = [];
            foreach ($row['attributes'] as $attribute) {
                if (null !== $type = AttributeType::tryFrom($attribute['type'])) {
                    $attributes[$attribute['name']] = new Attribute($attribute['name'], $type, $attribute['required'], $attribute['collection'], $attribute['options'], $attribute['filterable']);
                }
            }

            yield new Definition($row['name'], $row['label'], $row['security'], $attributes);
        }
    }

    public function generation(): int
    {
        try {
            return (int) $this->manager()->getConnection()->fetchOne('SELECT value FROM runtime_generation WHERE id = 1');
        } catch (TableNotFoundException) {
            return 0;
        }
    }

    public function publish(?callable $changes = null): int
    {
        return $this->manager()->wrapInTransaction(static function (EntityManagerInterface $manager) use ($changes): int {
            if (null !== $changes) {
                $changes($manager);
                $manager->flush();
            }

            $connection = $manager->getConnection();
            if (0 === $connection->executeStatement('UPDATE runtime_generation SET value = value + 1 WHERE id = 1')) {
                $connection->insert('runtime_generation', ['id' => 1, 'value' => 1]);
            }

            return (int) $connection->fetchOne('SELECT value FROM runtime_generation WHERE id = 1');
        });
    }

    private function manager(): EntityManagerInterface
    {
        $manager = $this->registry->getManagerForClass(Generation::class);
        \assert($manager instanceof EntityManagerInterface);

        return $manager;
    }
}
