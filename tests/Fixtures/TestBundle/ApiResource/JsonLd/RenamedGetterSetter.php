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
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'JsonLdRenamedGetterSetter',
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
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

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return [];
    }

    public static function process(mixed $data): mixed
    {
        return $data;
    }
}
