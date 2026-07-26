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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Doctrine\Odm\Filter\ChainFilter;
use ApiPlatform\Doctrine\Odm\Filter\ComparisonFilter;
use ApiPlatform\Doctrine\Odm\Filter\ExactFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[ApiResource]
#[GetCollection(
    paginationEnabled: false,
    parameters: [
        'quantity' => new QueryParameter(
            filter: new ChainFilter([
                new ExactFilter(),
                new ComparisonFilter(new ExactFilter()),
            ]),
            property: 'quantity',
            nativeType: new BuiltinType(TypeIdentifier::INT),
            castToNativeType: true,
        ),
    ],
)]
#[ODM\Document]
class ChainFilterParameter
{
    public function __construct(
        #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
        public ?int $id = null,

        #[ODM\Field(type: 'int')]
        public ?int $quantity = null,
    ) {
    }
}
