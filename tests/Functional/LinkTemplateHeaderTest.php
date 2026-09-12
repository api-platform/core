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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\LinkTemplateResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class LinkTemplateHeaderTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [LinkTemplateResource::class];
    }

    public function testTemplatedLinksAreSentInTheLinkTemplateHeader(): void
    {
        $response = self::createClient()->request('GET', '/link_templates/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Link-Template', '"/link_templates/{id}/author"; rel="author"');

        $link = $response->getHeaders()['link'][0] ?? '';
        $this->assertStringContainsString('</docs.jsonld>; rel="describedby"', $link);
        $this->assertStringNotContainsString('link_templates/{id}/author', $link);
    }
}
