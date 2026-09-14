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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DeserializedQueryInput\DeserializedQueryInput;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DeserializedQueryInputTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [DeserializedQueryInput::class];
    }

    public function testProcessorReceivesDeserializedInput(): void
    {
        DeserializedQueryInput::$processedName = null;

        self::createClient()->request('QUERY', '/deserialized_query_input', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['name' => 'criteria'],
        ]);

        $this->assertSame('criteria', DeserializedQueryInput::$processedName);
    }
}
