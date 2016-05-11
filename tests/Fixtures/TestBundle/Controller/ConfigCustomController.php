<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller;

use ApiPlatform\Core\Action\ActionUtilTrait;
use ApiPlatform\Core\Api\ItemDataProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Config Custom Controller.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ConfigCustomController
{
    use ActionUtilTrait;

    /**
     * @var DataProviderInterface
     */
    private $dataProvider;

    public function __construct(ItemDataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function __invoke(Request $request, $id)
    {
        list($resourceType) = $this->extractAttributes($request);

        return $this->dataProvider->getItem($resourceType, $id);
    }
}
