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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6465;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'foo6465')]
#[ApiResource(
    shortName: 'Foo6465',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Post(
            uriTemplate: '/foo/{id}/validate',
            uriVariables: ['id' => new Link(fromClass: Foo::class)],
            status: 200,
            input: CustomInput::class,
            output: CustomOutput::class,
            processor: [self::class, 'process'],
        ),
    ]
)]
class Foo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255)]
    public ?string $title = null;

    /**
     * @param CustomInput $data
     */
    public static function process($data): CustomOutput
    {
        return new CustomOutput($data->bar->title);
    }
}
