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

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'JsonLdNonResourceContainer',
    normalizationContext: ['groups' => ['jsonld_non_resource']],
    operations: [
        new Get(
            uriTemplate: '/jsonld_non_resource_containers/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
#[ApiFilter(PropertyFilter::class)]
class NonResourceContainer
{
    #[ApiProperty(identifier: true)]
    #[Groups(['jsonld_non_resource'])]
    public string $id;

    #[Groups(['jsonld_non_resource'])]
    public ?self $nested = null;

    #[Groups(['jsonld_non_resource'])]
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
        #[Groups(['jsonld_non_resource'])]
        public string $foo,
        #[Groups(['jsonld_non_resource'])]
        public string $bar,
    ) {
    }
}
