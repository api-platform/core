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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GraphQlCustomQueryProvider;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(provider: [AccountWithGet::class, 'provideForGet']),
    ],
    graphQlOperations: [
        new Query(provider: [AccountWithGet::class, 'provideForQuery']),
    ],
    normalizationContext: ['groups' => ['account_with_get:read']],
)]
final class AccountWithGet
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        #[Groups(['account_with_get:read'])]
        public string $id = '1',
        #[Groups(['account_with_get:read'])]
        public string $source = '',
    ) {
    }

    public static function provideForGet(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self(id: (string) ($uriVariables['id'] ?? '1'), source: 'http-get');
    }

    public static function provideForQuery(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self(id: (string) ($uriVariables['id'] ?? '1'), source: 'graphql-query');
    }
}
