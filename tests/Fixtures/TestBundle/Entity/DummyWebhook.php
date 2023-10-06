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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Attributes\Webhook;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(operations: [new Get(openapi: new Webhook(
    name: 'webhook',
    pathItem: new PathItem(
        get: new Operation(
            summary: 'Something else here',
            description: 'Something else here for example',
        ),
    )
)), new Post(openapi: new Webhook(
    name: 'webhook',
    pathItem: new PathItem(
        post: new Operation(
            summary: 'Something else here',
            description: 'Hi! it\'s me, I\'m the problem, it\'s me',
        ),
    )
)),
])]
#[ORM\Entity]
class DummyWebhook
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

}
