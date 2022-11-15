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

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * A legacy relation with a custom provider.
 *
 * @ApiResource
 */
class Issue5094Relation
{
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @ApiProperty(identifier=true)
     */
    public $id;
}
