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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ApiResource(operations: [new Get(), new Patch(inputFormats: ['json' => ['application/merge-patch+json'], 'jsonapi'], normalizationContext: ['skip_null_values' => true, 'groups' => ['chicago']]), new Post(), new GetCollection()], normalizationContext: ['groups' => ['chicago']], denormalizationContext: ['groups' => ['chicago']])]
#[ODM\Document]
class PatchDummyRelation
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public $id;
    #[Groups(['chicago'])]
    #[ODM\ReferenceOne(targetDocument: RelatedDummy::class)]
    protected $related;

    public function getRelated()
    {
        return $this->related;
    }

    public function setRelated(RelatedDummy $relatedDummy): void
    {
        $this->related = $relatedDummy;
    }
}
