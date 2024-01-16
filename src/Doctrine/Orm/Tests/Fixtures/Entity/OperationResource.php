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

namespace ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity;

use ApiPlatform\Doctrine\Orm\Tests\Fixtures\State\OperationResourceProcessor;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(normalizationContext: ['skip_null_values' => true], processor: OperationResourceProcessor::class)]
#[Get]
#[Patch(inputFormats: ['json' => ['application/merge-patch+json']])]
#[Post]
#[Put(extraProperties: ['standard_put' => false])]
#[Delete]
class OperationResource
{
    public function __construct(#[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type: 'integer')] #[ApiProperty(identifier: true)] private int $identifier, public $name)
    {
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public function getName()
    {
        return $this->name;
    }
}
