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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\SecuredInputDto;

#[ApiResource(
    operations: [
        new Get(provider: [self::class, 'provide']),
        new Patch(input: SecuredInputDto::class, processor: [self::class, 'process'], provider: [self::class, 'provide']),
    ]
)]
class DummyDtoSecuredInput
{
    public ?int $id = null;

    public ?string $title = null;

    public ?string $adminOnly = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $entity = new self();
        $entity->id = (int) $uriVariables['id'];
        $entity->title = 'existing title';
        $entity->adminOnly = 'existing admin value';

        return $entity;
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        if (!$data instanceof SecuredInputDto) {
            throw new \InvalidArgumentException('Expected SecuredInputDto');
        }

        $entity = $context['previous_data'] ?? new self();
        if (null !== $data->title) {
            $entity->title = $data->title;
        }
        if (null !== $data->adminOnly) {
            $entity->adminOnly = $data->adminOnly;
        }

        return $entity;
    }
}
