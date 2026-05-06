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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonLdRenamedGetterSetter',
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_renamed_getter_setters',
            provider: [self::class, 'provideCollection'],
        ),
        new Post(
            uriTemplate: '/jsonld_renamed_getter_setters',
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
        ),
    ],
)]
class RenamedGetterSetter
{
    private string $name = '';

    public function getFirstnameOnly(): string
    {
        return $this->name;
    }

    public function setFirstnameOnly(string $name): void
    {
        $this->name = $name;
    }

    public static function provide(): self
    {
        return new self();
    }

    public static function provideCollection(): array
    {
        return [];
    }

    public static function process(mixed $data): mixed
    {
        return $data;
    }
}
