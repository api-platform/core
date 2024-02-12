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

namespace ApiPlatform\OpenApi\Tests\Fixtures;

use ApiPlatform\Metadata\ApiResource;

/**
 * Dummy with a different short name.
 *
 * @author Priyadi Iman Nurcahyo <priyadi@rekalogika.com>
 */
#[ApiResource(shortName: 'DummyShortName')]
class DummyWithDifferentShortName
{
}
