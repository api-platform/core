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

namespace ApiPlatform\Tests\Fixtures\TestBundle\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\RecoverPasswordOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\User;

final class RecoverPasswordOutputDataTransformer implements DataTransformerInterface
{
    /**
     * @return object
     */
    public function transform($object, string $to, array $context = [])
    {
        $output = new RecoverPasswordOutput();
        $output->dummy = new Dummy();
        $output->dummy->setId(1);

        return $output;
    }

    public function supportsTransformation($object, string $to, array $context = []): bool
    {
        return $object instanceof User && RecoverPasswordOutput::class === $to;
    }
}
