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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterProvider\ReadLinkParameterProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;

#[Get(
    uriTemplate: '/write_uri_variable_link_resources/{id}',
    uriVariables: [
        'id' => new Link(
            provider: ReadLinkParameterProvider::class,
            fromClass: Dummy::class,
            extraProperties: ['write_uri_variable' => true],
        ),
    ],
    provider: [self::class, 'provide']
)]
class WriteUriVariableLinkResource
{
    public string $id;
    public string $dummyName;

    public static function provide(Operation $operation, array $uriVariables = [])
    {
        $r = new self();
        $r->id = '1';
        // Fails with a TypeError unless the resolved Dummy replaced the identifier.
        $r->dummyName = $uriVariables['id']->getName();

        return $r;
    }
}
