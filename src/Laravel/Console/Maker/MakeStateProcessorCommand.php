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

namespace ApiPlatform\Laravel\Console\Maker;

use ApiPlatform\Laravel\Console\Maker\Utils\StateTypeEnum;

final class MakeStateProcessorCommand extends AbstractMakeStateCommand
{
    protected $signature = 'make:state-processor';
    protected $description = 'Creates an API Platform state processor';

    protected function getStateType(): StateTypeEnum
    {
        return StateTypeEnum::Processor;
    }
}
