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

namespace ApiPlatform\GraphQl\Type;

use ApiPlatform\GraphQl\Type\Definition\TypeInterface;
use Psr\Container\ContainerInterface;

/**
 * Get the registered services corresponding to GraphQL types.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class TypesFactory implements TypesFactoryInterface
{
    private $typeLocator;
    private $typeIds;

    /**
     * @param string[] $typeIds
     */
    public function __construct(ContainerInterface $typeLocator, array $typeIds)
    {
        $this->typeLocator = $typeLocator;
        $this->typeIds = $typeIds;
    }

    public function getTypes(): array
    {
        $types = [];

        foreach ($this->typeIds as $typeId) {
            /** @var TypeInterface $type */
            $type = $this->typeLocator->get($typeId);
            $types[$type->getName()] = $type;
        }

        return $types;
    }
}

class_alias(TypesFactory::class, \ApiPlatform\Core\GraphQl\Type\TypesFactory::class);
