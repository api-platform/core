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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5896;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\TypeFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

class TypeFactoryDecorator implements TypeFactoryInterface
{
    public function __construct(
        private readonly TypeFactoryInterface $decorated,
    ) {
    }

    public function getType(Type $type, string $format = 'json', ?bool $readableLink = null, ?array $serializerContext = null, ?Schema $schema = null): array
    {
        if (is_a($type->getClassName(), LocalDate::class, true)) {
            return [
                'type' => 'string',
                'format' => 'date',
            ];
        }

        return $this->decorated->getType($type, $format, $readableLink, $serializerContext, $schema);
    }
}
