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

namespace ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/mercure_with_topics_and_get_operations/{id}{._format}'),
        new Post(uriTemplate: '/mercure_with_topics_and_get_operations{._format}'),
        new Get(uriTemplate: '/custom_resource/mercure_with_topics_and_get_operations/{id}{._format}'),
    ],
    mercure: [
        'topics' => [
            '@=iri(object)',
            '@=iri(object, '.UrlGeneratorInterface::ABS_URL.', get_operation(object, "/custom_resource/mercure_with_topics_and_get_operations/{id}{._format}"))',
        ],
    ]
)]
#[ORM\Entity]
class MercureWithTopicsAndGetOperation
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;
    #[ORM\Column]
    public $name;
}
