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

namespace ApiPlatform\Metadata;

use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;

interface OpenApiParameterFilterInterface
{
    /**
     * @return OpenApiParameter|OpenApiParameter[]|null
     */
    public function getOpenApiParameters(Parameter $parameter): OpenApiParameter|array|null;
}
