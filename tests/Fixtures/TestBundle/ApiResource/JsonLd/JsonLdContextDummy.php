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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(
    shortName: 'JsonLdContextDummy',
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
class JsonLdContextDummy
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    #[ApiProperty(iris: ['https://schema.org/name'])]
    public ?string $name = null;

    #[ApiProperty(iris: ['https://schema.org/alternateName'])]
    public ?string $alias = null;

    #[ApiProperty(jsonldContext: ['@id' => 'https://example.com/id', '@type' => '@id', 'foo' => 'bar'])]
    public ?string $person = null;

    public ?JsonLdContextRelation $related = null;

    /**
     * Exercises the collection-valued relation context mapping.
     *
     * @var JsonLdContextRelation[]
     */
    public array $relatedCollection = [];

    #[ApiProperty(readableLink: true)]
    public ?JsonLdContextRelation $embedded = null;

    #[ApiProperty(iris: ['https://schema.org/DateTime'])]
    public ?\DateTimeInterface $dummyDate = null;

    public ?array $arrayData = null;

    public mixed $jsonData = null;

    public ?string $nameConverted = null;

    public static function provide(): array
    {
        return [];
    }

    public static function process(self $data): self
    {
        $data->id = 1;

        return $data;
    }
}
