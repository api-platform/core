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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_ADMIN\')'), new GetCollection(security: 'is_granted(\'ROLE_ADMIN\')')], graphQlOperations: [new Query(name: 'item_query', security: 'is_granted(\'ROLE_ADMIN\')'), new QueryCollection(name: 'collection_query', security: 'is_granted(\'ROLE_ADMIN\')')], security: 'is_granted(\'ROLE_ADMIN\')')]
#[ORM\Entity]
class RelatedSecuredDummy
{
    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }
}
