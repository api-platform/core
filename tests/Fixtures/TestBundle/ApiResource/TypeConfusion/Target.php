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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\TypeConfusion;

use ApiPlatform\Metadata\Post;

#[Post(uriTemplate: '/type-confusion/targets{._format}')]
class Target
{
    public ?string $name = null;

    /**
     * Untyped (PHPDoc-only) so PropertyAccessor cannot enforce the declared class
     * at write time. This is the legacy style the security advisory targets.
     *
     * @var Foo|null
     */
    public $relation;
}
