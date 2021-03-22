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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Orm;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ObjectRepository;

final class EntityManager extends EntityManagerDecorator
{
    public static $dql;

    private $repositoryFactory;

    public function __construct(EntityManagerInterface $wrapped, RepositoryFactory $repositoryFactory)
    {
        parent::__construct($wrapped);

        $this->repositoryFactory = $repositoryFactory;
    }

    public function getRepository($className): ObjectRepository
    {
        return $this->repositoryFactory->getRepository($this, $className);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    public function createQuery($dql = ''): Query
    {
        self::$dql = $dql;

        return $this->wrapped->createQuery($dql);
    }
}
