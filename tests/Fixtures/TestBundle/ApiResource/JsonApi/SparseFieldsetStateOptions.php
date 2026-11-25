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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\JsonApi\Filter\SparseFieldset;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SparseFieldsetStateOptionsEntity;

#[ApiResource(
    shortName: 'SparseFieldsetStateOptions',
    stateOptions: new Options(entityClass: SparseFieldsetStateOptionsEntity::class),
    operations: [
        new Get(
            uriTemplate: '/sparse_fieldset_state_options/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
    parameters: ['fields' => new QueryParameter(filter: SparseFieldset::class)],
)]
final class SparseFieldsetStateOptions
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id,
        public string $title,
        public int $totalTracks,
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        $id = (int) ($uriVariables['id'] ?? 1);

        return new self($id, 'Title #'.$id, 12);
    }
}
