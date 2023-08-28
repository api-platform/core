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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5396;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;

#[ApiResource(provider: [CompositeKeyWithDifferentType::class, 'provide'])]
class CompositeKeyWithDifferentType
{
    #[ApiProperty(identifier: true)]
    public ?int $id;

    #[ApiProperty(identifier: true)]
    public ?string $verificationKey;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        if (!\is_string($uriVariables['verificationKey'])) {
            throw new \RuntimeException('verificationKey should be a string.');
        }

        $t = new self();
        $t->id = $uriVariables['id'];
        $t->verificationKey = $uriVariables['verificationKey'];

        return $t;
    }
}
