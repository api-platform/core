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

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Product as ProductDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;

class ProductProvider implements ProviderInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly bool $orm = true)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Product|ProductDocument|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            // todo Handle collection of products
            return null;
        }

        return $this->managerRegistry->getRepository($this->orm ? Product::class : ProductDocument::class)->findOneBy([
            'code' => $uriVariables['code'],
        ]);
    }
}
