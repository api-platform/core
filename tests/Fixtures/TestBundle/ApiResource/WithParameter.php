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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Parameter\CustomGroupParameterProvider;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Serializer\Attribute\Groups;

#[Get(
    uriTemplate: 'with_parameters/{id}{._format}',
    uriVariables: [
        'id' => new Link(schema: ['type' => 'uuid'], property: 'id'),
    ],
    parameters: [
        'groups' => new QueryParameter(filter: new GroupFilter(parameterName: 'groups', overrideDefaultGroups: false)),
        'group' => new QueryParameter(provider: [self::class, 'provideGroup']),
        'properties' => new QueryParameter(filter: 'my_dummy.property'),
        'service' => new QueryParameter(provider: CustomGroupParameterProvider::class),
        'auth' => new HeaderParameter(provider: [self::class, 'restrictAccess']),
        'priority' => new QueryParameter(provider: [self::class, 'assertSecond'], priority: 10),
        'priorityb' => new QueryParameter(provider: [self::class, 'assertFirst'], priority: 20),
        'array' => new QueryParameter(provider: [self::class, 'assertArray']),
    ],
    provider: [self::class, 'provide']
)]
#[GetCollection(
    uriTemplate: 'with_parameters_collection',
    parameters: [
        'hydra' => new QueryParameter(property: 'a', required: true),
    ],
    provider: [self::class, 'collectionProvider']
)]
#[QueryParameter(key: 'everywhere')]
class WithParameter
{
    protected static int $counter = 1;
    public int $id = 1;

    #[Groups(['a'])]
    public $a = 'foo';
    #[Groups(['b', 'custom'])]
    public $b = 'bar';

    public static function collectionProvider()
    {
        return [new self()];
    }

    public static function provide()
    {
        return new self();
    }

    public static function assertArray(): void
    {
    }

    public static function assertFirst(): void
    {
        \assert(1 === static::$counter);
        ++static::$counter;
    }

    public static function assertSecond(): void
    {
        \assert(2 === static::$counter);
    }

    public static function provideGroup(Parameter $parameter, array $parameters = [], array $context = [])
    {
        $operation = $context['operation'];

        return $operation->withNormalizationContext(['groups' => $parameters['group']]);
    }

    public static function restrictAccess(): void
    {
        throw new AccessDeniedHttpException();
    }
}
