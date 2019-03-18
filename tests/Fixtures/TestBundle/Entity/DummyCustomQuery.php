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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dummy with a custom GraphQL query resolver (@link DummyCustomQueryResolver).
 *
 * @author Lukas Lücke <lukas@luecke.me>
 *
 * @ApiResource(graphql={
 *		"test"={
 *			"item_query"="app.graphql.query_resolver.dummy_custom"
 *		}
 *	})
 */
class DummyCustomQuery
{
	/**
     * @var string
     */
    public $message;
}
