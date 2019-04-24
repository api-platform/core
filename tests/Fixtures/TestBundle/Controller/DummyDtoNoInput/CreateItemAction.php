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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\DummyDtoNoInput;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\DummyDtoNoInput as DummyDtoNoInputDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;
use Symfony\Component\HttpFoundation\Request;

final class CreateItemAction
{
    public function __invoke(Request $request)
    {
        $resourceClass = $request->attributes->get('_api_resource_class');
        if (!\in_array($resourceClass, [DummyDtoNoInput::class, DummyDtoNoInputDocument::class], true)) {
            throw new \InvalidArgumentException();
        }

        $data = new $resourceClass();

        $data->lorem = 'test';
        $data->ipsum = 1;

        return $data;
    }
}
