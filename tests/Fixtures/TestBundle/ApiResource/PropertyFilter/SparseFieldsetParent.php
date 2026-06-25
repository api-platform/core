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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PropertyFilter;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Serializer\Filter\PropertyFilter;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/sparse_fieldset_parents/{id}',
        uriVariables: ['id'],
        provider: [self::class, 'provide'],
    ),
], parameters: ['properties' => new QueryParameter(filter: new PropertyFilter())])]
final class SparseFieldsetParent
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id,
        public string $name,
        public string $alias,
        public string $nameConverted,
        public ?SparseFieldsetChild $child = null,
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        $id = (int) $uriVariables['id'];

        return new self(
            $id,
            'Parent #'.$id,
            'Alias #'.$id,
            'Converted '.$id,
            new SparseFieldsetChild($id, 'Child #'.$id, 'A description'),
        );
    }
}
