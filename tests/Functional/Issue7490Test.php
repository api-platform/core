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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7490\FileUploadResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class Issue7490Test extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FileUploadResource::class];
    }

    public function testInvalidDataUriExceptionIsNotWrapped(): void
    {
        $response = self::createClient()->request('POST', '/issue7490_file_uploads', [
            'json' => [
                'file' => 'data:invalid',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');

        $responseData = $response->toArray(false);

        // The original DataUriNormalizer exception message should be preserved
        // and not wrapped with a generic type mismatch message
        $this->assertStringContainsString('The provided "data:" URI is not valid', $responseData['hydra:description'] ?? $responseData['detail']);

        // The error should NOT contain the wrapped message
        $this->assertStringNotContainsString('The type of the "file" attribute must be', $responseData['hydra:description'] ?? $responseData['detail']);
    }
}
