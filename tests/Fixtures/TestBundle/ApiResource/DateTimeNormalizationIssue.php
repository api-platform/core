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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/datetime_normalization_issues/{id}',
            provider: [self::class, 'provide']
        ),
    ]
)]
class DateTimeNormalizationIssue
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?\DateTimeImmutable $updatedAt = null,
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self(
            id: (int) ($uriVariables['id'] ?? 1),
            name: 'Test Resource',
            updatedAt: new \DateTimeImmutable('2024-01-15T10:30:00+00:00'),
        );
    }
}
