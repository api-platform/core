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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ItemUriTemplateWithoutFormatSuffix;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/uri_template_format_suffix_resource_collection',
            itemUriTemplate: '/uri_template_format_suffix_resource_items/{id}',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            provider: [self::class, 'provide'],
        ),
        new Get(
            uriTemplate: '/uri_template_format_suffix_resource_items/{id}{._format}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class UriTemplateFormatSuffixResource
{
    public function __construct(#[ApiProperty(identifier: true)] public string $id = '1')
    {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self((string) ($uriVariables['id'] ?? '1'));
    }

    /**
     * @return self[]
     */
    public static function provideCollection(): array
    {
        return [new self('1'), new self('2')];
    }
}
