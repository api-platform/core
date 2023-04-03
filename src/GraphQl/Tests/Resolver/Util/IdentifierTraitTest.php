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

namespace ApiPlatform\GraphQl\Tests\Resolver\Util;

use ApiPlatform\GraphQl\Resolver\Util\IdentifierTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class IdentifierTraitTest extends TestCase
{
    private function getIdentifierTraitImplementation()
    {
        return new class() {
            use IdentifierTrait {
                IdentifierTrait::getIdentifierFromContext as public;
            }
        };
    }

    public function testGetIdentifierFromQueryContext(): void
    {
        $identifierTrait = $this->getIdentifierTraitImplementation();

        $this->assertSame('foo', $identifierTrait->getIdentifierFromContext(['args' => ['id' => 'foo'], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => false]));
    }

    public function testGetIdentifierFromMutationContext(): void
    {
        $identifierTrait = $this->getIdentifierTraitImplementation();

        $this->assertSame('foo', $identifierTrait->getIdentifierFromContext(['args' => ['input' => ['id' => 'foo']], 'is_collection' => false, 'is_mutation' => true, 'is_subscription' => false]));
    }

    public function testGetIdentifierFromSubscriptionContext(): void
    {
        $identifierTrait = $this->getIdentifierTraitImplementation();

        $this->assertSame('foo', $identifierTrait->getIdentifierFromContext(['args' => ['input' => ['id' => 'foo']], 'is_collection' => false, 'is_mutation' => false, 'is_subscription' => true]));
    }
}
