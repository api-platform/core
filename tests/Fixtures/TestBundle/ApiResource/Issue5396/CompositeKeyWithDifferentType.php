<?php

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5396;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;

#[ApiResource(provider: [self::class, 'provide'])]
class CompositeKeyWithDifferentType {

    #[ApiProperty(identifier: true)]
    private ?int $id;

    #[ApiProperty(identifier: true)]
    private ?string $verificationKey;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []) {
        return $context;
    }
}
