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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface as LegacyQueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Context aware extension.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ContextAwareQueryCollectionExtensionInterface extends QueryCollectionExtensionInterface
{
    /**
     * {@inheritdoc}
     *
     * @param LegacyQueryNameGeneratorInterface|QueryNameGeneratorInterface $queryNameGenerator
     */
    public function applyToCollection(QueryBuilder $queryBuilder, LegacyQueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = []);
}
