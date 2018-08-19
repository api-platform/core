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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface;

abstract class AbstractContextAwareFilter extends AbstractFilter implements ContextAwareFilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply($builder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (!isset($context['filters']) || !\is_array($context['filters'])) {
            parent::apply($builder, $queryNameGenerator, $resourceClass, $operationName, $context);

            return;
        }

        foreach ($context['filters'] as $property => $value) {
            $this->filterProperty($property, $value, $builder, $queryNameGenerator, $resourceClass, $operationName, $context);
        }
    }
}
