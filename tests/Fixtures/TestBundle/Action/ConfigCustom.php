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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Action;

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Config Custom Controller.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ConfigCustom
{
    public function __construct(private readonly ProviderInterface $provider)
    {
    }

    public function __invoke(Request $request, $id)
    {
        $attributes = RequestAttributesExtractor::extractAttributes($request);

        return $this->provider->provide(new Get(class: $attributes['resource_class']), ['id' => $id]);
    }
}
