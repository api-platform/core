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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

final class FakeProvider implements ProviderInterface
{
    /**
     * @return array|object|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $className = $operation->getClass();
        $data = [
            '12345' => new $className('12345', 'Vincent'),
            '67890' => new $className('67890', 'Grégoire'),
        ];

        if (isset($uriVariables['id'])) {
            return $data[$uriVariables['id']] ?? null;
        }

        return array_values($data);
    }
}
