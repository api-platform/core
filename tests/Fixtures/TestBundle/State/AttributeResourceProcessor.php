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

use ApiPlatform\Metadata\AbstractOperation;
use ApiPlatform\State\ProcessorInterface;

class AttributeResourceProcessor
{
    /**
     * {@inheritDoc}
     */
    static public function process($data, AbstractOperation $operation, array $uriVariables = [], array $context = [])
    {
    }
}
