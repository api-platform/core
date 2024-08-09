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

namespace ApiPlatform\Tests\Symfony\Bundle\Test\Constraint;

use ApiPlatform\Symfony\Bundle\Test\Constraint\MatchesJsonSchema;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class MatchesJsonSchemaTest extends TestCase
{
    public function testAdditionalFailureDescription(): void
    {
        $jsonSchema = <<<JSON
{
  "required": ["notexist"]
}
JSON;

        $constraint = new MatchesJsonSchema($jsonSchema);

        try {
            $constraint->evaluate(['foo' => 'bar']);
            $this->fail(\sprintf('Expected %s to be thrown.', ExpectationFailedException::class));
        } catch (ExpectationFailedException $expectedException) {
            $this->assertStringContainsString('notexist: The property notexist is required', $expectedException->getMessage());
        }
    }
}
