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

namespace ApiPlatform\Core\JsonSchema\Type;

use ApiPlatform\Core\JsonSchema\Schema;
use ApiPlatform\Core\JsonSchema\TypeFactoryInterface;
use Elao\Enum\EnumInterface;
use Symfony\Component\PropertyInfo\Type;

final class ElaoEnumType implements TypeFactoryInterface
{
    /**
     * @var TypeFactoryInterface
     */
    private $decorated;

    public function __construct(TypeFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(Type $type, string $format = 'json', ?bool $readableLink = null, ?array $serializerContext = null, Schema $schema = null): array
    {
        if (!is_a($enumClass = $type->getClassName(), EnumInterface::class, true)) {
            return $this->decorated->getType($type, $format, $readableLink, $serializerContext, $schema);
        }

        $values = $enumClass::values();

        return [
            'type' => 'string',
            'enum' => $values,
            'example' => $values[0],
        ];
    }
}
