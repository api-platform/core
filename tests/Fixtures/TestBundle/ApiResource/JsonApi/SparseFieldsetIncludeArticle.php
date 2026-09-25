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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Serializer\Filter\PropertyFilter;

#[ApiResource(
    shortName: 'SparseFieldsetIncludeArticle',
    operations: [
        new Get(
            uriTemplate: '/sparse_fieldset_include_articles/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
#[ApiFilter(PropertyFilter::class)]
final class SparseFieldsetIncludeArticle
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id,
        public string $title,
        public string $body,
        public ?SparseFieldsetIncludeAuthor $author = null,
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        $id = (int) ($uriVariables['id'] ?? 1);

        return new self($id, 'Title #'.$id, 'Body #'.$id, SparseFieldsetIncludeAuthor::provide($operation, ['id' => 1]));
    }
}
