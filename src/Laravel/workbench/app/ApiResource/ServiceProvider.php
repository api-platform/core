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

namespace Workbench\App\ApiResource;

use ApiPlatform\Metadata\Get;
use Workbench\App\State\CustomProvider;
use Workbench\App\State\CustomProviderWithDependency;
use Workbench\App\State\TeapotProvider;

#[Get(uriTemplate: 'teapot', name: 'teapot', provider: TeapotProvider::class)]
#[Get(uriTemplate: 'custom_service_provider', provider: CustomProvider::class)]
#[Get(uriTemplate: 'custom_service_provider_with_dependency', provider: CustomProviderWithDependency::class)]
class ServiceProvider
{
}
