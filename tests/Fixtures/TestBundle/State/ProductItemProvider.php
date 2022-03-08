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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Product as ProductDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Product;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\ProductInterface;
use Doctrine\Persistence\ManagerRegistry;

class ProductItemProvider implements ProviderInterface
{
    private $managerRegistry;
    private $orm;

    public function __construct(ManagerRegistry $managerRegistry, bool $orm = true)
    {
        $this->managerRegistry = $managerRegistry;
        $this->orm = $orm;
    }

    /**
     * {@inheritDoc}
     */
    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = [])
    {
        return $this->managerRegistry->getRepository($this->orm ? Product::class : ProductDocument::class)->findOneBy([
            'code' => $identifiers['code'],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        /** @var Operation */
        $operation = $context['operation'] ?? new Get();

        return is_a($resourceClass, ProductInterface::class, true) && !$operation->isCollection();
    }
}
