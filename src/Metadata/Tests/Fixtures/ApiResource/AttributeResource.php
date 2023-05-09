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

namespace ApiPlatform\Metadata\Tests\Fixtures\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Tests\Fixtures\State\AttributeResourceProcessor;
use ApiPlatform\Metadata\Tests\Fixtures\State\AttributeResourceProvider;

#[ApiResource(
    normalizationContext: ['skip_null_values' => true],
    provider: AttributeResourceProvider::class
)]
#[Get]
#[Put]
#[Delete]
#[ApiResource(
    '/dummy/{dummyId}/attribute_resources/{identifier}{._format}',
    inputFormats: ['json' => ['application/merge-patch+json']],
    status: 301,
    provider: AttributeResourceProvider::class,
    processor: [AttributeResourceProcessor::class, 'process']
)]
#[Get]
#[Patch]
final class AttributeResource
{
    /**
     * @var ?Dummy
     */
    #[Link('dummyId')]
    public $dummy;

    public function __construct(#[ApiProperty(identifier: true)] private int $identifier, public string $name)
    {
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
