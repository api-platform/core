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
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Serializer\Filter\GroupFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Parameter\CustomGroupParameterProvider;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Serializer\Attribute\Groups;

#[Get(
    parameters: [
        'groups' => new QueryParameter(filter: new GroupFilter(parameterName: 'groups', overrideDefaultGroups: false)),
        'group' => new QueryParameter(provider: [self::class, 'provideGroup']),
        'properties' => new QueryParameter(filter: 'my_dummy.property'),
        'service' => new QueryParameter(provider: CustomGroupParameterProvider::class),
        'auth' => new HeaderParameter(provider: [self::class, 'restrictAccess']),
    ],
    provider: [WithParameter::class, 'provide']
)]
class WithParameter
{
    #[Groups(['a'])]
    public $a = 'foo';
    #[Groups(['b', 'custom'])]
    public $b = 'bar';

    public static function provide()
    {
        return new self();
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
