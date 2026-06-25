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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\Legacy;

use ApiPlatform\Doctrine\Odm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Legacy regression fixture: keeps the deprecated BooleanFilter alive until 6.0.
 * The canonical replacement (ExactFilter + boolean nativeType) lives at
 * Document\FilteredBooleanParameter.
 */
#[ApiResource]
#[GetCollection(
    uriTemplate: 'legacy_filtered_boolean_parameters{._format}',
    parameters: [
        'active' => new QueryParameter(
            filter: new BooleanFilter(),
            nativeType: new BuiltinType(TypeIdentifier::BOOL),
        ),
        'enabled' => new QueryParameter(
            filter: new BooleanFilter(),
            property: 'active',
            nativeType: new BuiltinType(TypeIdentifier::BOOL),
        ),
    ],
)]
#[ODM\Document]
class FilteredBooleanParameter
{
    public function __construct(
        #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
        public ?int $id = null,

        #[ODM\Field(type: 'bool', nullable: true)]
        public ?bool $active = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): void
    {
        $this->active = $active;
    }
}
