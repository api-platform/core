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

use ApiPlatform\Doctrine\Odm\Filter\ExactFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

#[ApiResource]
#[GetCollection(
    paginationItemsPerPage: 5,
    parameters: [
        'quantity' => new QueryParameter(
            filter: new ExactFilter(),
            nativeType: new BuiltinType(TypeIdentifier::INT),
            castToNativeType: true,
        ),
        'amount' => new QueryParameter(
            filter: new ExactFilter(),
            property: 'quantity',
            nativeType: new BuiltinType(TypeIdentifier::INT),
            castToNativeType: true,
        ),
        'ratio' => new QueryParameter(
            filter: new ExactFilter(),
            nativeType: new BuiltinType(TypeIdentifier::FLOAT),
            castToNativeType: true,
        ),
    ],
)]
#[ODM\Document]
class FilteredNumericParameter
{
    public function __construct(
        #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
        public ?int $id = null,

        #[ODM\Field(type: 'int', nullable: true)]
        public ?int $quantity = null,

        #[ODM\Field(type: 'float', nullable: true)]
        public ?float $ratio = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getRatio(): ?float
    {
        return $this->ratio;
    }

    public function setRatio(?float $ratio): void
    {
        $this->ratio = $ratio;
    }
}
