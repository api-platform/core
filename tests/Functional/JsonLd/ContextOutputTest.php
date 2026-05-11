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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\GenIdFalse;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6810\JsonLdContextOutput;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class ContextOutputTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [JsonLdContextOutput::class, GenIdFalse::class];
    }

    public function testContextOnOutputDtoMatchesDeclaredVocabulary(): void
    {
        $response = self::createClient()->request('GET', '/json_ld_context_output');
        $res = $response->toArray();
        $this->assertEquals($res['@context'], [
            '@vocab' => 'http://localhost/docs.jsonld#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'foo' => 'Output/foo',
        ]);
    }

    public function testIgnoredPropertyIsExcludedFromResourceContext(): void
    {
        $r = self::createClient()->request('GET', '/contexts/GenIdFalse');
        $this->assertArrayNotHasKey('shouldBeIgnored', $r->toArray()['@context']);
    }
}
