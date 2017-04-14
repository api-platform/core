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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Action;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Config Custom Controller.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ConfigCustom
{
    private $dataProvider;

    public function __construct(ItemDataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function __invoke(Request $request, $id)
    {
        $attributes = RequestAttributesExtractor::extractAttributes($request);

        return $this->dataProvider->getItem($attributes['resource_class'], $id);
    }
}
