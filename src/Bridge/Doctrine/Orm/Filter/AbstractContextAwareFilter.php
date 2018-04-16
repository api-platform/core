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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Dkd\Populate\Exception;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Debug\ExceptionHandler;
use TYPO3\Flow\Error\DebugExceptionHandler;

abstract class AbstractContextAwareFilter extends AbstractFilter implements ContextAwareFilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (!isset($context['filters']) || !\is_array($context['filters'])) {
            parent::apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);

            return;
        }

        foreach ($context['filters'] as $filterName => $filterValue) {
            if (isset($this->properties[$filterName])) {
                continue;
            }
            $alternativeFilterName = str_replace('_', '.', $filterName);
            if (!isset($this->properties[$alternativeFilterName])) {
                continue;
            }
            unset($context['filters'][$filterName]);
            $context['filters'][$alternativeFilterName] = $filterValue;
        }

        foreach ($context['filters'] as $property => $value) {
            $this->filterProperty($property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName,
                $context);
        }
    }
}
