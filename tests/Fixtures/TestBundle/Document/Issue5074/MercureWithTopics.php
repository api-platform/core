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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue5074;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/issue5074/mercure_with_topics/{id}{._format}'),
        new Post(uriTemplate: '/issue5074/mercure_with_topics{._format}'),
    ],
    mercure: ['topics' => '@=iri(object)'],
    extraProperties: ['standard_put' => false]
)]
#[ODM\Document]
class MercureWithTopics
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public $id;
    #[ODM\Field(type: 'string')]
    public $name;
}
