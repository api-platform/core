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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'HalNonResourceContainer',
    normalizationContext: ['groups' => ['hal_non_resource']],
    operations: [
        new Get(
            uriTemplate: '/hal_non_resource_containers/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class NonResourceContainer
{
    #[ApiProperty(identifier: true)]
    #[Groups(['hal_non_resource'])]
    public string $id;

    #[Groups(['hal_non_resource'])]
    public ?self $nested = null;

    #[Groups(['hal_non_resource'])]
    public ?NonResourceClass $notAResource = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $root = new self();
        $root->id = (string) ($uriVariables['id'] ?? '1');
        $root->notAResource = new NonResourceClass('f1', 'b1');

        $nested = new self();
        $nested->id = $root->id.'-nested';
        $nested->notAResource = new NonResourceClass('f2', 'b2');
        $root->nested = $nested;

        return $root;
    }
}

final class NonResourceClass
{
    public function __construct(
        #[Groups(['hal_non_resource'])]
        public string $foo,
        #[Groups(['hal_non_resource'])]
        public string $bar,
    ) {
    }
}
