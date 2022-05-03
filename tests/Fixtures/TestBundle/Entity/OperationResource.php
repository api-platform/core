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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Tests\Fixtures\TestBundle\State\OperationResourceProcessor;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(normalizationContext: ['skip_null_values' => true], processor: OperationResourceProcessor::class)]
#[Get]
#[Patch(inputFormats: ['json' => ['application/merge-patch+json']])]
#[Post]
#[Put]
#[Delete]
#[ORM\Entity]
class OperationResource
{
    public function __construct(#[ApiProperty(identifier: true)] #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type: 'integer')] private readonly int $identifier, public string $name)
    {
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getName()
    {
        return $this->name;
    }
}
