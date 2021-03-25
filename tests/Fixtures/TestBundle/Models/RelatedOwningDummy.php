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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Models;

use ApiPlatform\Core\Annotation\ApiResource;
use Illuminate\Database\Eloquent\Model;

/**
 * Related Owning Dummy.
 *
 * @author Sergey V. Ryabov <sryabov@mhds.ru>
 *
 * @ApiResource(iri="https://schema.org/Product")
 */
class RelatedOwningDummy extends Model
{
    public $timestamps = false;
}
