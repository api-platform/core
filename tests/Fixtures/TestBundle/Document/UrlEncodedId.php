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
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author Daniel West <daniel@silverback.is>
 *
 * Resource with an ID that will be URL encoded
 */
#[ApiResource(operations: [new Get(requirements: ['id' => '.+']), new Post(), new GetCollection()])]
#[ODM\Document]
class UrlEncodedId
{
    #[ODM\Id(strategy: 'none')]
    private string $id = '%encode:id';

    public function getId(): string
    {
        return $this->id;
    }
}
