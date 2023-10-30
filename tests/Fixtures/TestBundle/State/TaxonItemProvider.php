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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Taxon as TaxonDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Taxon;
use Doctrine\Persistence\ManagerRegistry;

class TaxonItemProvider implements ProviderInterface
{
    private $managerRegistry;
    private $orm;

    public function __construct(ManagerRegistry $managerRegistry, bool $orm = true)
    {
        $this->managerRegistry = $managerRegistry;
        $this->orm = $orm;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        return $this->managerRegistry->getRepository($this->orm ? Taxon::class : TaxonDocument::class)->findOneBy([
            'code' => $uriVariables['code'],
        ]);
    }
}
