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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Test\Constraint;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Constraint\MatchesJsonSchema;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;

class MatchesJsonSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        if (version_compare(Version::id(), '8.0.0', '<')) {
            $this->markTestSkipped('Requires PHPUnit 8');
        }
    }

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
            $this->fail(sprintf('Expected %s to be thrown.', ExpectationFailedException::class));
        } catch (ExpectationFailedException $expectedException) {
            $this->assertStringContainsString('notexist: The property notexist is required', $expectedException->getMessage());
        }
    }
}
