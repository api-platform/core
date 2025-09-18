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
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterProvider\ReadLinkParameterProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;

#[Get(
    uriTemplate: '/link_parameter_provider_resources/{id}',
    uriVariables: [
        'id' => new Link(
            provider: ReadLinkParameterProvider::class,
            fromClass: Dummy::class
        ),
    ],
    provider: [self::class, 'provide']
)]
class LinkParameterProviderResource
{
    public string $id;
    public Dummy $dummy;

    /**
     * @param HttpOperation $operation
     */
    public static function provide(Operation $operation, array $uriVariables = [])
    {
        $d = new self();
        $d->id = '1';
        $d->dummy = $operation->getUriVariables()['id']->getValue();

        return $d;
    }
}
