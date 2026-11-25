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
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'Issue8702Book',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new Get(uriTemplate: '/issue8702_books/{id}', provider: [self::class, 'provide']),
        new Post(uriTemplate: '/issue8702_books', processor: [self::class, 'process']),
    ],
)]
final class Issue8702Book
{
    /**
     * @param list<Issue8702Author> $coAuthors
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id = 1,
        public string $title = 'Dune',
        public ?Issue8702Author $author = null,
        public array $coAuthors = [],
        public array $payload = [],
    ) {
    }

    public static function provide(): null
    {
        return null;
    }

    public static function process(self $data): self
    {
        return $data;
    }
}
