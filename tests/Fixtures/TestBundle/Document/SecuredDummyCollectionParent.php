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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\NotExposed;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Secured resource.
 */
#[ApiResource(
    operations: [
        new NotExposed(),
    ],
    graphQlOperations: [
        new Query(),
        new QueryCollection(),
    ],
    security: 'is_granted(\'ROLE_USER\')'
)]
#[ODM\Document]
class SecuredDummyCollectionParent
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    public ?int $id = null;

    #[ODM\ReferenceOne(targetDocument: SecuredDummyCollection::class)]
    public ?SecuredDummyCollection $child = null;
}
