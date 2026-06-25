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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Dto;

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\QueryParameter;

/**
 * Spike (RFC 10008 QUERY): a criteria DTO used as the input of a Query operation. The
 * #[QueryParameter] attributes on its properties both describe the documented request body
 * and drive the filtering of the underlying resource.
 */
final class QueryMethodCriteria
{
    #[QueryParameter(filter: new PartialSearchFilter())]
    public ?string $name = null;
}
