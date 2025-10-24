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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7432;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;

#[Patch(
    provider: [self::class, 'provide'],
    uriTemplate: '/original_data_with_listeners/{uuid}/verify',
    uriVariables: ['uuid'],
    input: UserVerifyInput::class,
    processor: [self::class, 'process']
)]
class OriginalDataWithListeners
{
    public function __construct(public string $uuid, public ?string $code = null)
    {
    }

    public static function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        \assert($data instanceof UserVerifyInput);
        \assert($context['previous_data'] instanceof self);
        \assert($context['read_data'] instanceof self);
        \assert($context['previous_data'] !== $context['read_data']);
        \assert($context['request']->attributes->get('data') instanceof UserVerifyInput);
        \assert($context['request']->attributes->get('read_data') instanceof self);
        \assert($context['request']->attributes->get('previous_data') instanceof self);
        \assert($context['data'] instanceof UserVerifyInput);
        $context['previous_data']->code = $data->code;

        return $context['previous_data'];
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        return new self($uriVariables['uuid']);
    }
}

class UserVerifyInput
{
    public ?string $code = null;
}
