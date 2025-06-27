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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class BackedEnumProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array
    {
        $resourceClass = $operation->getClass();
        if (!$resourceClass || !is_a($resourceClass, \BackedEnum::class, true)) {
            throw new RuntimeException('This resource is not an enum');
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $resourceClass::cases();
        }

        $id = $uriVariables['id'] ?? null;
        if (null === $id) {
            throw new NotFoundHttpException('Not Found');
        }

        if ($enum = $this->resolveEnum($resourceClass, $id)) {
            return $enum;
        }

        throw new NotFoundHttpException('Not Found');
    }

    /**
     * @param class-string $resourceClass
     */
    private function resolveEnum(string $resourceClass, string|int $id): ?\BackedEnum
    {
        $reflectEnum = new \ReflectionEnum($resourceClass);
        $type = (string) $reflectEnum->getBackingType();

        if ('int' === $type) {
            if (!is_numeric($id)) {
                return null;
            }

            return $resourceClass::tryFrom((int) $id);
        }

        return $resourceClass::tryFrom($id);
    }
}
