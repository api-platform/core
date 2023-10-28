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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;

#[Get(
    uriTemplate: '/subresource_categories/{id}',
    provider: [SubresourceCategory::class, 'provideNull']
)]
#[Get(
    uriTemplate: '/subresource_categories_with_create_provider/{id}',
    provider: [SubresourceCategory::class, 'provide']
)]
/**
 * @see SubresourceBike
 */
final class SubresourceCategory
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public ?int $id = null,
        public ?string $name = null
    ) {
    }

    public static function provideNull()
    {
        return null;
    }

    public static function provide(): self
    {
        return new self(1, 'Hello World!');
    }
}
