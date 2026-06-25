<?php

declare(strict_types=1);

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DeserializedQueryInput\DeserializedQueryCriteria;
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
        DeserializedQueryInput::$processedData = null;

        self::createClient()->request('QUERY', '/deserialized_query_input', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['name' => 'criteria'],
        ]);

        $this->assertInstanceOf(DeserializedQueryCriteria::class, DeserializedQueryInput::$processedData);
        $this->assertSame('criteria', DeserializedQueryInput::$processedData->name);
    }
}
