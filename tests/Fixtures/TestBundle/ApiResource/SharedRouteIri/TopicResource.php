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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SharedRouteIri;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[ApiResource(shortName: 'SharedIriTopic', operations: [
    new Get(uriTemplate: '/shared_iri/topics/{id}', provider: [self::class, 'provide']),
    new Post(uriTemplate: '/shared_iri/topics', processor: [self::class, 'process']),
])]
final class TopicResource
{
    public ?int $id = null;
    public string $title = '';
    public ?CategoryProjection $category = null;

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        $topic = new self();
        $topic->id = (int) $uriVariables['id'];
        $topic->title = 'Topic '.$uriVariables['id'];
        $topic->category = new CategoryProjection(1, 'Category 1');

        return $topic;
    }

    public static function process(self $data): self
    {
        $data->id = 42;

        return $data;
    }
}
