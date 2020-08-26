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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Taxon as TaxonDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Taxon;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Model\TaxonInterface;
use Doctrine\Persistence\ManagerRegistry;

class TaxonItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $managerRegistry;
    private $orm;

    public function __construct(ManagerRegistry $managerRegistry, bool $orm = true)
    {
        $this->managerRegistry = $managerRegistry;
        $this->orm = $orm;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return is_a($resourceClass, TaxonInterface::class, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        return $this->managerRegistry->getRepository($this->orm ? Taxon::class : TaxonDocument::class)->findOneBy([
            'code' => $id,
        ]);
    }
}
