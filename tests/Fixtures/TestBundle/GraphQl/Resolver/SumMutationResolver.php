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

namespace ApiPlatform\Tests\Fixtures\TestBundle\GraphQl\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyCustomMutation as DummyCustomMutationDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCustomMutation;

/**
 * Resolver for custom mutation.
 *
 * @author Raoul Clais <raoul.clais@gmail.com>
 */
class SumMutationResolver implements MutationResolverInterface
{
    /**
     * @param DummyCustomMutation|DummyCustomMutationDocument|null $item
     *
     * @return DummyCustomMutation|DummyCustomMutationDocument
     */
    public function __invoke($item, array $context)
    {
        if (null !== $operandC = $context['args']['input']['operandC'] ?? null) {
            $item->setResult((int) $operandC);

            return $item;
        }

        $item->setResult($item->getOperandA() + $item->getOperandB());

        return $item;
    }
}
