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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ItemUriTemplateWithoutFormatSuffix\UriTemplateFormatSuffixResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ItemUriTemplateWithoutFormatSuffixTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [UriTemplateFormatSuffixResource::class];
    }

    public function testItemUriTemplateResolvesWithoutFormatSuffix(): void
    {
        $response = self::createClient()->request('GET', '/uri_template_format_suffix_resource_collection');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame('/uri_template_format_suffix_resource_items/1', $data['hydra:member'][0]['@id']);
        $this->assertSame('/uri_template_format_suffix_resource_items/2', $data['hydra:member'][1]['@id']);
    }
}
