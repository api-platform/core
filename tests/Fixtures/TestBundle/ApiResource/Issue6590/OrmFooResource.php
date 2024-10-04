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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6590;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6590\Foo;
use ApiPlatform\Tests\Fixtures\TestBundle\State\Issue6590\FooResourceProvider;

#[ApiResource(
    shortName: 'Issue6590OrmFoo',
    operations: [],
    graphQlOperations: [
        new Query(),
        new QueryCollection(),
    ],
    provider: FooResourceProvider::class,
    stateOptions: new Options(entityClass: Foo::class)
)]
class OrmFooResource
{
    #[ApiProperty(identifier: true)]
    public int $id;

    /**
     * @var OrmBarResource[]
     */
    public array $bars;

    public function addBar(OrmBarResource $bar): void
    {
        $this->bars[] = $bar;
    }

    public function removeBar(OrmBarResource $bar): void
    {
        unset($this->bars[array_search($bar, $this->bars, true)]);
    }
}
