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
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;

class RecoverPasswordProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RecoverPasswordOutput
    {
        // Because we're in a PUT operation, we will use the retrieved object...
        $resourceObject = $context['previous_data'] ?? new $context['resource_class']();
        // ...where we remove the credentials
        $resourceObject->eraseCredentials();

        $output = new RecoverPasswordOutput();
        $output->dummy = new Dummy();
        $output->dummy->setId(1);

        return $output;
    }
}
