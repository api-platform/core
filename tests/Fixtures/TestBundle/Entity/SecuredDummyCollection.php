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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\NotExposed;
use Doctrine\ORM\Mapping as ORM;

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
#[ORM\Entity]
class SecuredDummyCollection
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    /**
     * @var string The title
     */
    #[ORM\Column]
    public string $title;

    /**
     * @var string Secret property, only readable/writable by owners
     */
    #[ApiProperty(security: 'object == null or object.owner == user', securityPostDenormalize: 'object.owner == user')]
    #[ORM\Column]
    public ?string $ownerOnlyProperty = null;

    /**
     * @var string The owner
     */
    #[ORM\Column]
    public string $owner;
}
