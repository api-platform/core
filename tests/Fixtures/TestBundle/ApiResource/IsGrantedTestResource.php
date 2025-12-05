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
        new Get(uriTemplate: 'is_granted_tests/{id}', security: 'is_granted("ROLE_ADMIN")', uriVariables: ['id'], provider: [self::class, 'provide']),
        new Get(uriTemplate: 'is_granted_test_call_provider/{id}', uriVariables: ['id'], security: 'is_granted("ROLE_ADMIN")', provider: [self::class, 'provideShouldNotBeCalled']),
    ]
)]
class IsGrantedTestResource
{
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        return new self();
    }

    public static function provideShouldNotBeCalled(Operation $operation, array $uriVariables = [], array $context = [])
    {
        throw new \RuntimeException('provider should not get called');
    }
}
