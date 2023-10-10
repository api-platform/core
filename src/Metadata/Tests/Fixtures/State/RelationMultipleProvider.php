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

namespace ApiPlatform\Metadata\Tests\Fixtures\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationMultiple;

class RelationMultipleProvider implements ProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $firstDummy = new Dummy();
        $firstDummy->setId($uriVariables['firstId']);
        $secondDummy = new Dummy();
        $relationMultiple = new RelationMultiple();
        $relationMultiple->id = 1;
        $relationMultiple->first = $firstDummy;
        $relationMultiple->second = $secondDummy;

        if ($operation instanceof GetCollection) {
            $secondDummy->setId(2);
            $thirdDummy = new Dummy();
            $thirdDummy->setId(3);
            $relationMultiple2 = new RelationMultiple();
            $relationMultiple2->id = 2;
            $relationMultiple2->first = $firstDummy;
            $relationMultiple2->second = $thirdDummy;

            return [$relationMultiple, $relationMultiple2];
        }

        $relationMultiple->second->setId($uriVariables['secondId']);

        return $relationMultiple;
    }
}
