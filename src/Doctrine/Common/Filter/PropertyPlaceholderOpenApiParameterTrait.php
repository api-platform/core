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

namespace ApiPlatform\Doctrine\Common\Filter;

use ApiPlatform\Metadata\Parameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;

trait PropertyPlaceholderOpenApiParameterTrait
{
    /**
     * @return array<OpenApiParameter>|null
     */
    public function getOpenApiParameters(Parameter $parameter): ?array
    {
        return [new OpenApiParameter(name: $parameter->getKey(), in: 'query')];
    }
}
