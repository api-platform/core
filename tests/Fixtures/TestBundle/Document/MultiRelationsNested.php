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

use ApiPlatform\GraphQl\State\Provider\NoopProvider;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ApiResource(graphQlOperations: [new QueryCollection(paginationEnabled: false, provider: NoopProvider::class, nested: true)])]
#[ODM\EmbeddedDocument]
class MultiRelationsNested
{
    #[ODM\Field(type: 'string')]
    public ?string $name;
}
