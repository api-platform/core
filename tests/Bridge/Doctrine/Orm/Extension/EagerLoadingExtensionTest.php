<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class EagerLoadingExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testApplyToCollection()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getAssociationNames()->shouldBeCalled()->willReturn([0 => 'dummyRelation']);
        $classMetadataProphecy->associationMappings = ['dummyRelation' => ['fetch' => 3]];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy->leftJoin('o.dummyRelation', 'a0')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('a0')->shouldBeCalled(1);

        $em = $queryBuilderProphecy->getEntityManager()->shouldBeCalled(1)->willReturn($emProphecy->reveal());

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new EagerLoadingExtension();
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToItem()
    {
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getAssociationNames()->shouldBeCalled()->willReturn([0 => 'dummyRelation']);
        $classMetadataProphecy->associationMappings = ['dummyRelation' => ['fetch' => 3]];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy->leftJoin('o.dummyRelation', 'a0')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('a0')->shouldBeCalled(1);

        $em = $queryBuilderProphecy->getEntityManager()->shouldBeCalled(1)->willReturn($emProphecy->reveal());

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new EagerLoadingExtension();
        $orderExtensionTest->applyToItem($queryBuilder, new QueryNameGenerator(), Dummy::class, []);
    }
}
