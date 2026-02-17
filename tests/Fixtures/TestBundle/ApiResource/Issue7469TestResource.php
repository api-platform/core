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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterProvider\ReadLinkParameterProvider;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7469Dummy;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/issue_7469_test_resources/{id}',
            uriVariables: [
                'id' => new Link(
                    provider: ReadLinkParameterProvider::class,
                    fromClass: Issue7469Dummy::class
                ),
            ],
            provider: [self::class, 'provide']
        ),
    ]
)]
final class Issue7469TestResource
{
    public int $id;
    public string $dummyName;

    /**
     * @param HttpOperation $operation
     */
    public static function provide(Operation $operation): self
    {
        /** @var Issue7469Dummy $dummy */
        $dummy = $operation->getUriVariables()['id']->getValue();

        $resource = new self();
        $resource->id = $dummy->id;
        $resource->dummyName = $dummy->name;

        return $resource;
    }
}
