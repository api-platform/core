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
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [],
    graphQlOperations: [
        new Query(provider: [Account::class, 'provide']),
    ],
    normalizationContext: ['groups' => ['account:read']],
)]
final class Account
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        #[Groups(['account:read'])]
        public string $id = '1',
        /**
         * @var list<array{key: string}>
         */
        #[Groups(['account:read'])]
        public array $credentials = [],
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self(id: (string) ($uriVariables['id'] ?? '1'), credentials: [['key' => 'static-value']]);
    }
}
