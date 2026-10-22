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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8702;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    shortName: 'Issue8702Author',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [new Get(uriTemplate: '/issue8702_authors/{id}', provider: [self::class, 'provide'])],
)]
final class Issue8702Author
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id = 1,
        public string $name = 'Frank Herbert',
    ) {
    }

    public static function provide(): self
    {
        return new self();
    }
}
