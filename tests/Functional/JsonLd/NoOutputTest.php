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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\NoOutputMessage;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class NoOutputTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [NoOutputMessage::class];
    }

    public function testPostWithOutputFalseReturns202AndEmptyBody(): void
    {
        $response = self::createClient()->request('POST', '/jsonld_no_output_messages', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => new \stdClass(),
        ]);

        $this->assertResponseStatusCodeSame(202);
        $this->assertEmpty($response->getContent());
    }
}
