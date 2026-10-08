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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class IdentifierTraitTest extends TestCase
{
    private function getIdentifierTraitImplementation()
    {
        return new class {
            use IdentifierTrait {
                IdentifierTrait::getIdentifierFromOperation as public;
            }
        };
    }

    public function testGetIdentifierFromQueryOperation(): void
    {
        $identifierTrait = $this->getIdentifierTraitImplementation();

        $this->assertSame('foo', $identifierTrait->getIdentifierFromOperation(new Query(), ['id' => 'foo']));
    }

    public function testGetIdentifierFromMutationOperation(): void
    {
        $identifierTrait = $this->getIdentifierTraitImplementation();

        $this->assertSame('foo', $identifierTrait->getIdentifierFromOperation(new Mutation(), ['input' => ['id' => 'foo']]));
    }

    public function testGetIdentifierFromSubscriptionOperation(): void
    {
        $identifierTrait = $this->getIdentifierTraitImplementation();

        $this->assertSame('foo', $identifierTrait->getIdentifierFromOperation(new Subscription(), ['input' => ['id' => 'foo']]));
    }
}
