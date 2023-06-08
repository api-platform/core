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

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Component\Uid\Ulid;

/**
 * @author Beno!t POLASZEK <bpolaszek@gmail.com>
 *
 * Resource with an Uid-based ID
 */
#[ApiResource(operations: [new Get(), new Post(), new GetCollection()])]
#[ApiFilter(SearchFilter::class, properties: ['id' => 'exact'])]
#[ODM\Document]
class UidBasedId
{
    #[ODM\Id(strategy: 'none')]
    public Ulid $id;

    public function __construct(?Ulid $id)
    {
        $this->id = $id ?? new Ulid();
    }
}
